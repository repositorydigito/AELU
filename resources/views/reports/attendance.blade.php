<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia - {{ $workshopData['name'] ?? '' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 15px; }

        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; }
        .header h1 { margin: 0 0 3px 0; font-size: 14px; text-transform: uppercase; color: #1e3a5f; }
        .header p { margin: 1px 0; font-size: 9px; color: #555; }

        .workshop-info { margin-bottom: 10px; padding: 6px 10px; border: 1px solid #ccc; background: #f8fafc; font-size: 10px; }
        .workshop-info table { width: 100%; border-collapse: collapse; }
        .workshop-info td { padding: 2px 8px 2px 0; border: none; }
        .workshop-info .label { font-weight: bold; color: #1e3a5f; width: 90px; }

        table.attendance { width: 100%; border-collapse: collapse; margin-top: 4px; }

        table.attendance th {
            background-color: #1e3a5f;
            color: #fff;
            padding: 5px 3px;
            font-size: 9px;
            border: 1px solid #1e3a5f;
            text-align: center;
        }
        table.attendance th.left { text-align: left; }

        table.attendance td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            font-size: 9px;
            vertical-align: middle;
        }
        table.attendance td.center { text-align: center; }
        table.attendance td.num { text-align: right; padding-right: 5px; }

        .present { font-weight: bold; text-align: center; }

        tr:nth-child(even) td { background-color: #f9fafb; }

        .totals-row td {
            background-color: #e5e7eb !important;
            font-weight: bold;
            border-top: 2px solid #555;
        }

        .footer { margin-top: 10px; font-size: 8px; color: #888; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista de Asistencia</h1>
        <p>Generado el {{ $generatedAt }}</p>
    </div>

    <div class="workshop-info">
        <table>
            <tr>
                <td class="label">Taller:</td>
                <td><strong>{{ $workshopData['name'] ?? '' }}</strong></td>
                <td class="label">Período:</td>
                <td>{{ $workshopData['period_name'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Instructor:</td>
                <td>{{ $workshopData['instructor_name'] ?? '' }}</td>
                <td class="label">Modalidad:</td>
                <td>{{ ucfirst($workshopData['modality'] ?? '') }}</td>
            </tr>
            <tr>
                <td class="label">Horario:</td>
                <td>
                    {{ is_array($workshopData['day_of_week'] ?? null) ? implode('/', $workshopData['day_of_week']) : ($workshopData['day_of_week'] ?? '') }}
                    &nbsp; {{ $workshopData['start_time'] ?? '' }} - {{ $workshopData['end_time'] ?? '' }}
                </td>
                <td class="label">Estudiantes:</td>
                <td>{{ count($studentEnrollments) }}</td>
            </tr>
        </table>
    </div>

    <table class="attendance">
        <thead>
            <tr>
                <th class="left" style="width:20px">N°</th>
                <th class="left" style="min-width:140px">Apellidos y Nombres</th>
                <th style="width:70px">Código</th>
                <th style="width:40px">Clases</th>
                @foreach($workshopClasses as $index => $class)
                    @php $date = \Carbon\Carbon::parse($class['class_date'])->format('d/m') @endphp
                    <th style="width:38px">Clase {{ $index + 1 }}<br><span style="font-weight:normal;font-size:8px">{{ $date }}</span></th>
                @endforeach
                <th style="width:38px">Total</th>
                <th style="min-width:80px;text-align:left">Comentarios</th>
            </tr>
        </thead>
        <tbody>
            @foreach($studentEnrollments as $i => $enrollment)
                @php
                    $totalPresent = 0;
                    $enrolledClassIds = $enrollment['enrolled_class_ids'] ?? [];
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $enrollment['student']['last_names'] }}, {{ $enrollment['student']['first_names'] }}</td>
                    <td class="center">{{ $enrollment['student']['student_code'] }}</td>
                    <td class="center">{{ $enrollment['number_of_classes'] }}</td>
                    @foreach($workshopClasses as $class)
                        @php
                            $key = $enrollment['id'] . '_' . $class['id'];
                            $isEnrolled = in_array($class['id'], $enrolledClassIds);
                            $isPresent = $attendanceData[$key]['is_present'] ?? false;
                            if ($isEnrolled && $isPresent) $totalPresent++;
                        @endphp
                        @if(!$isEnrolled)
                            <td class="center"></td>
                        @elseif($isPresent)
                            <td class="present">P</td>
                        @else
                            <td class="center"></td>
                        @endif
                    @endforeach
                    <td class="center"><strong>{{ $totalPresent }}</strong></td>
                    @php
                        $firstClassId = $workshopClasses[0]['id'] ?? null;
                        $commentKey = $enrollment['id'] . '_' . $firstClassId;
                        $comment = $attendanceData[$commentKey]['comments'] ?? '';
                    @endphp
                    <td style="font-size:8px;vertical-align:top">{{ $comment }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td></td>
                <td colspan="3">TOTAL PRESENTES POR CLASE</td>
                @foreach($workshopClasses as $class)
                    @php
                        $count = 0;
                        foreach ($studentEnrollments as $enrollment) {
                            $key = $enrollment['id'] . '_' . $class['id'];
                            $enrolledClassIds = $enrollment['enrolled_class_ids'] ?? [];
                            if (in_array($class['id'], $enrolledClassIds) && ($attendanceData[$key]['is_present'] ?? false)) {
                                $count++;
                            }
                        }
                    @endphp
                    <td class="center">{{ $count }}</td>
                @endforeach
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        PAMA &bull; Sistema de Gestión de Talleres
    </div>
</body>
</html>
