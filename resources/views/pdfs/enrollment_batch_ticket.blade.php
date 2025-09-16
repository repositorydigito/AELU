<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket de Inscripción - {{ $student->last_names ?? 'N/A'}} {{ $student->first_names ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            font-size: 12px;
            line-height: 1.2;
            color: #333;
        }

        .ticket-container {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
        }

        .header h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .student-name {
            margin: 0;
            padding: 2px 5px;
            border: 1px solid #000;
            font-size: 14px;
            font-weight: bold;
        }

        .user-fields {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }

        .user-field {
            border: 1px solid #000;
            padding: 2px 5px;
            width: 45%;
            text-align: center;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
            align-items: center;
        }

        .info-label {
            font-weight: bold;
            width: 30%;
            border: 1px solid #000;
            padding: 3px 5px;
            background-color: #f0f0f0;
        }

        .info-value {
            border: 1px solid #000;
            padding: 3px 5px;
            flex: 1;
            margin-left: -1px;
        }

        .workshops-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .workshops-table th,
        .workshops-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
            vertical-align: middle;
        }

        .workshops-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .workshops-table td {
            vertical-align: middle;
        }

        .taller-col { width: 30%; }
        .horario-col { width: 20%; }
        .fechas-col {
            width: 20%;
            overflow: hidden;
            word-wrap: break-word;
        }
        .clases-col { width: 10%; }
        .importe-col { width: 20%; }

        .class-dates {
            font-size: 9px;
            line-height: 1.3;
            text-align: center;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
        }

        .class-date-item {
            white-space: nowrap;
            display: inline-block;
        }

        .footer-section {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .footer-label {
            font-weight: bold;
        }

        .total-words {
            margin-top: 5px;
            border: 1px solid #000;
            padding: 5px;
            background-color: #f9f9f9;
        }

        .size-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="header">
            <h1>PROGRAMA ADULTO MAYOR AELU (PAMA)</h1>
            <div class="student-name">
                {{ $student->last_names ?? 'N/A' }}, {{ $student->first_names ?? 'N/A' }}
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">USUARIO</div>
            <div class="info-value">{{ $created_by_user ?? 'Admin' }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">TICKET N°</div>
            <div class="info-value">{{ $enrollmentBatch->batch_code ?? str_pad($enrollmentBatch->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="info-label">FECHA</div>
            <div class="info-value">{{ $enrollmentBatch->enrollment_date->format('d/m/Y') }}</div>
        </div>

        <div class="info-row">
            <div class="info-label">CÓDIGO</div>
            <div class="info-value">{{ $student->student_code ?? 'N/A' }}</div>
        </div>

        <table class="workshops-table">
            <thead>
                <tr>
                    <th class="taller-col">TALLER</th>
                    <th class="horario-col">HORARIO</th>
                    <th class="clases-col">N° CLASES</th>
                    <th class="fechas-col">FECHAS DE CLASES</th>
                    <th class="importe-col">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollmentBatch->enrollments as $enrollment)
                    @php
                        $workshop = $enrollment->instructorWorkshop;
                        $dayNames = [
                            1 => 'lunes', 2 => 'martes', 3 => 'miércoles',
                            4 => 'jueves', 5 => 'viernes', 6 => 'sábado',
                            7 => 'domingo', 0 => 'domingo'
                        ];
                        $dayInSpanish = $dayNames[$workshop->day_of_week] ?? 'día ' . $workshop->day_of_week;
                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('g:i a.');
                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('g:i a.');

                        // Obtener las fechas de las clases específicas a las que se inscribió
                        $enrollmentClasses = $enrollment->enrollmentClasses()
                            ->with('workshopClass')
                            ->orderBy('created_at', 'asc')
                            ->get();

                        $classDates = [];
                        foreach ($enrollmentClasses as $enrollmentClass) {
                            if ($enrollmentClass->workshopClass) {
                                $classDate = \Carbon\Carbon::parse($enrollmentClass->workshopClass->class_date);
                                $classDates[] = $classDate->format('d/m');
                            }
                        }

                        // Si no hay clases específicas, mostrar todas las clases del taller
                        if (empty($classDates) && $workshop->workshop) {
                            $workshopClasses = \App\Models\WorkshopClass::where('workshop_id', $workshop->workshop->id)
                                ->where('monthly_period_id', $enrollment->monthly_period_id)
                                ->orderBy('class_date', 'asc')
                                ->take($enrollment->number_of_classes)
                                ->get();

                            foreach ($workshopClasses as $workshopClass) {
                                $classDate = \Carbon\Carbon::parse($workshopClass->class_date);
                                $classDates[] = $classDate->format('d/m');
                            }
                        }
                    @endphp
                    <tr>
                        <td style="text-align: left;">{{ strtoupper($workshop->workshop->name) }}</td>
                        <td>{{ $dayInSpanish }}<br>{{ $startTime }} - {{ $endTime }}</td>
                        <td>{{ $enrollment->number_of_classes }}</td>
                        <td>
                            <div class="class-dates">
                                @if(!empty($classDates))
                                    @foreach($classDates as $date)
                                        <div class="class-date-item">{{ $date }} || </div>
                                    @endforeach
                                @else
                                    <div class="class-date-item">Por definir</div>
                                @endif
                            </div>
                        </td>
                        <td>{{ number_format($enrollment->total_amount, 2) }}</td>
                    </tr>
                @endforeach

                <!-- Fila del total -->
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="4" style="text-align: right; padding-right: 10px;">IMPORTE TOTAL:</td>
                    <td>S/ {{ number_format($enrollmentBatch->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer-section">
            <div class="footer-row">
                <span><strong>MES PAGADO:</strong>
                    @if($enrollmentBatch->enrollments->first() && $enrollmentBatch->enrollments->first()->monthlyPeriod)
                        {{ strtoupper(\Carbon\Carbon::createFromDate($enrollmentBatch->enrollments->first()->monthlyPeriod->year, $enrollmentBatch->enrollments->first()->monthlyPeriod->month)->translatedFormat('F Y')) }}
                    @else
                        {{ strtoupper(\Carbon\Carbon::parse($enrollmentBatch->enrollment_date)->translatedFormat('F Y')) }}
                    @endif
                </span>
            </div>

            <div class="total-words">
                <strong>SON:</strong> {{ $totalInWords ?? 'CIENTO DIEZ Y 00/100 SOLES' }}
            </div>
        </div>
    </div>
</body>
</html>
