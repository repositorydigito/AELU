<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    use Exportable;

    // Filas de info que se insertarán encima de la tabla
    private const INFO_ROWS = 6; // 5 datos + 1 vacía

    public function __construct(
        protected array $workshopData,
        protected array $workshopClasses,
        protected array $studentEnrollments,
        protected array $attendanceData,
    ) {}

    /**
     * Encabezados de la tabla — WithHeadings los escribe en fila 1,
     * que es la única fila con soporte garantizado de estilo entero en WithStyles.
     */
    public function headings(): array
    {
        $header = ['N°', 'Apellidos y Nombres', 'Código', 'N° Clases'];

        foreach ($this->workshopClasses as $index => $class) {
            $date     = Carbon::parse($class['class_date'])->format('d/m/Y');
            $header[] = 'Clase ' . ($index + 1) . "\n" . $date;
        }

        $header[] = 'Total';
        $header[] = 'Comentarios';

        return $header;
    }

    /**
     * Datos de estudiantes (sin encabezado, sin info del taller).
     * Fila 1 = headings, fila 2..N = datos, fila N+1 = totales.
     */
    public function collection()
    {
        $rows = collect();

        foreach ($this->studentEnrollments as $i => $enrollment) {
            $totalPresent = 0;
            $row          = [
                $i + 1,
                ($enrollment['student']['last_names'] ?? '') . ', ' . ($enrollment['student']['first_names'] ?? ''),
                $enrollment['student']['student_code'] ?? '',
                $enrollment['number_of_classes'] ?? '',
            ];

            foreach ($this->workshopClasses as $class) {
                $key             = $enrollment['id'] . '_' . $class['id'];
                $enrolledClassIds = $enrollment['enrolled_class_ids'] ?? [];

                if (!in_array($class['id'], $enrolledClassIds)) {
                    $row[] = '';
                } elseif ($this->attendanceData[$key]['is_present'] ?? false) {
                    $row[] = 'P';
                    $totalPresent++;
                } else {
                    $row[] = '';
                }
            }

            $row[] = $totalPresent;

            // Comentarios: misma lógica que el blade (primer taller)
            $firstClassId = $this->workshopClasses[0]['id'] ?? null;
            $row[]        = $this->attendanceData[$enrollment['id'] . '_' . $firstClassId]['comments'] ?? '';

            $rows->push($row);
        }

        // Fila de totales por clase
        $totalRow = ['', 'TOTAL PRESENTES', '', ''];
        foreach ($this->workshopClasses as $class) {
            $count = 0;
            foreach ($this->studentEnrollments as $enrollment) {
                $key             = $enrollment['id'] . '_' . $class['id'];
                $enrolledClassIds = $enrollment['enrolled_class_ids'] ?? [];
                if (in_array($class['id'], $enrolledClassIds) && ($this->attendanceData[$key]['is_present'] ?? false)) {
                    $count++;
                }
            }
            $totalRow[] = $count;
        }
        $totalRow[] = '';
        $totalRow[] = '';
        $rows->push($totalRow);

        return $rows;
    }

    /**
     * Estilo del encabezado en fila 1 — este patrón (1 => [...]) funciona
     * igual que en los demás exports del proyecto.
     */
    public function styles(Worksheet $sheet): array
    {
        $classCount = count($this->workshopClasses);
        $lastCol    = Coordinate::stringFromColumnIndex(4 + $classCount + 2);

        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e3a5f'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ],
            'A1:' . $lastCol . (count($this->studentEnrollments) + 2) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }

    /**
     * AfterSheet: inserta el bloque de info del taller encima de la tabla
     * y aplica colores a las celdas de asistencia.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet        = $event->sheet->getDelegate();
                $classCount   = count($this->workshopClasses);
                $studentCount = count($this->studentEnrollments);
                $totalCols    = 4 + $classCount + 2;
                $lastCol      = Coordinate::stringFromColumnIndex($totalCols);

                // Al insertar INFO_ROWS filas, el encabezado baja de fila 1 → fila (INFO_ROWS + 1)
                $sheet->insertNewRowBefore(1, self::INFO_ROWS);

                $headerRow   = self::INFO_ROWS + 1; // = fila 7
                $dataStart   = $headerRow + 1;       // = fila 8
                $lastDataRow = $dataStart + $studentCount; // última fila (totales)

                // Altura del encabezado para acomodar el salto de línea en "Clase N\nfecha"
                $sheet->getRowDimension($headerRow)->setRowHeight(32);

                // --- Bloque de info del taller ---
                $days = is_array($this->workshopData['day_of_week'] ?? null)
                    ? implode('/', $this->workshopData['day_of_week'])
                    : ($this->workshopData['day_of_week'] ?? '');

                $infoRows = [
                    ['TALLER:',     $this->workshopData['name'] ?? ''],
                    ['INSTRUCTOR:', $this->workshopData['instructor_name'] ?? ''],
                    ['HORARIO:',    $days . '  ' . ($this->workshopData['start_time'] ?? '') . ' - ' . ($this->workshopData['end_time'] ?? '')],
                    ['MODALIDAD:',  ucfirst($this->workshopData['modality'] ?? '')],
                    ['PERÍODO:',    $this->workshopData['period_name'] ?? ''],
                ];

                foreach ($infoRows as $i => $info) {
                    $r = $i + 1;
                    $sheet->setCellValue('A' . $r, $info[0]);
                    $sheet->setCellValue('B' . $r, $info[1]);
                }

                // Etiquetas en negrita
                $sheet->getStyle('A1:A5')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // --- Fila de totales (última fila de datos) ---
                $sheet->getStyle('A' . $lastDataRow . ':' . $lastCol . $lastDataRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e5e7eb']],
                ]);

                // --- Colores P / A / ∅ ---
                foreach ($this->studentEnrollments as $i => $enrollment) {
                    $row         = $dataStart + $i;
                    $enrolledIds = $enrollment['enrolled_class_ids'] ?? [];

                    foreach ($this->workshopClasses as $j => $class) {
                        $col = Coordinate::stringFromColumnIndex(5 + $j);
                        $key = $enrollment['id'] . '_' . $class['id'];

                        if ($this->attendanceData[$key]['is_present'] ?? false) {
                            $sheet->getStyle($col . $row)->applyFromArray([
                                'font'      => ['bold' => true],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                        }
                    }

                    // Total: centrado y negrita
                    $totalCol = Coordinate::stringFromColumnIndex(5 + $classCount);
                    $sheet->getStyle($totalCol . $row)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'font'      => ['bold' => true],
                    ]);

                    // Comentarios: wrap text
                    $commentCol = Coordinate::stringFromColumnIndex(5 + $classCount + 1);
                    $sheet->getStyle($commentCol . $row)->applyFromArray([
                        'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                    ]);
                }

                // Ancho fijo para comentarios (desactiva autosize en esa columna)
                $commentColLetter = Coordinate::stringFromColumnIndex(5 + $classCount + 1);
                $sheet->getColumnDimension($commentColLetter)->setAutoSize(false);
                $sheet->getColumnDimension($commentColLetter)->setWidth(35);
            },
        ];
    }

    public function title(): string
    {
        return 'Asistencia';
    }
}
