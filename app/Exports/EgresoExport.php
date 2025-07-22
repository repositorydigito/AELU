<?php

namespace App\Exports;

use App\Models\ExpenseDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;

class EgresoExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = ExpenseDetail::query()
            ->with(['expense']);

        // Aplicar filtros de fecha de gasto si existen
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_until'])) {
            $query->whereDate('date', '<=', $this->filters['date_until']);
        }

        // Aplicar filtros de fecha de registro si existen
        if (!empty($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from']);
        }

        if (!empty($this->filters['created_until'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_until']);
        }

        // Aplicar filtro de concepto si existe
        if (!empty($this->filters['concept'])) {
            $query->whereHas('expense', function ($q) {
                $q->where('concept', $this->filters['concept']);
            });
        }

        // Aplicar filtros de rango de monto si existen
        if (!empty($this->filters['amount_from'])) {
            $query->where('amount', '>=', $this->filters['amount_from']);
        }

        if (!empty($this->filters['amount_until'])) {
            $query->where('amount', '<=', $this->filters['amount_until']);
        }

        // Aplicar filtro de proveedor si existe
        if (!empty($this->filters['proveedor'])) {
            $query->where('razon_social', 'like', "%{$this->filters['proveedor']}%");
        }

        // Aplicar filtro de voucher si existe
        if (isset($this->filters['has_voucher'])) {
            if ($this->filters['has_voucher'] === true) {
                $query->whereHas('expense', function ($q) {
                    $q->whereNotNull('voucher_path');
                });
            } elseif ($this->filters['has_voucher'] === false) {
                $query->whereHas('expense', function ($q) {
                    $q->whereNull('voucher_path');
                });
            }
        }

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Fecha del Gasto',
            'Concepto',
            'Proveedor / Razón Social',
            'N° Documento',
            'Monto (S/)',
            'Observaciones',
            'Código de Vale',
            'Fecha de Registro',
            'Tiene Voucher'
        ];
    }

    public function map($expenseDetail): array
    {
        return [
            $expenseDetail->date->format('d/m/Y'),
            $this->formatConcept($expenseDetail->expense->concept),
            $expenseDetail->razon_social,
            $expenseDetail->document_number,
            number_format($expenseDetail->amount, 2),
            $expenseDetail->notes ?? 'Sin observaciones',
            $expenseDetail->expense->vale_code ?? 'Sin código',
            $expenseDetail->created_at->format('d/m/Y H:i'),
            !empty($expenseDetail->expense->voucher_path) ? 'Sí' : 'No'
        ];
    }

    private function formatConcept(string $concept): string
    {
        return match ($concept) {
            'Taller de Cocina' => 'Taller de Cocina',
            'Compra de materiales' => 'Compra de materiales',
            'Pago a Profesores' => 'Pago a profesores',
            'Otros' => 'Otros',
            default => $concept,
        };
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC2626'] // Color rojo para egresos
                ]
            ],
            // Bordes para toda la tabla
            'A1:I1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]
        ];
    }
}