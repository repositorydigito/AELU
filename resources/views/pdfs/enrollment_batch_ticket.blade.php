<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket de Inscripción - {{ $student->last_names ?? 'N/A'}} {{ $student->first_names ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 1.5mm;
            padding: 0;
            font-size: 11px;
            line-height: 1.13;
            color: #333;
        }

        .ticket-container {
            border: 1.2px solid #000;
            padding: 6px 4px 4px 4px;
            min-height: 0;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .header h1 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: bold;
        }

        .student-info {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 6px;
            text-align: center;
        }

        .student-name-row {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .student-code {
            display: inline;
        }

        .student-details {
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #666;
            padding-top: 2px;
        }

        .workshops-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }

        .workshops-table th,
        .workshops-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .workshops-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10.5px;
        }

    .taller-col { width: 27%; text-align: left; }
    .horario-col { width: 20%; }
    .clases-col { width: 10%; }
    .fechas-col { width: 25%; }
    .importe-col { width: 18%; }

        .class-dates {
            font-size: 10px;
            line-height: 1.13;
            text-align: center;
        }

        .class-date-item {
            display: inline;
            white-space: nowrap;
        }

        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }

        .footer-section {
            margin-top: 6px;
            border-top: 1.2px solid #000;
            padding-top: 6px;
            font-size: 11px;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 4px 6px;
            background-color: #f9f9f9;
            border: 1.2px solid #000;
            gap: 12px;
        }

        .footer-left, .footer-right {
            font-weight: bold;
            font-size: 11px;
        }

        .total-words {
            margin-top: 6px;
            border: 1.2px solid #000;
            padding: 4px 6px;
            background-color: #f9f9f9;
            font-size: 11px;
            text-align: center;
            font-weight: bold;
        }

        .compact-text {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- Header optimizado -->
        <div class="header">
            <h1>PROGRAMA ADULTO MAYOR AELU (PAMA)</h1>
        </div>

        <!-- Información del estudiante con código integrado -->
        <div class="student-info">
            <div class="student-name-row">
                {{ $student->last_names ?? 'N/A' }}, {{ $student->first_names ?? 'N/A' }} - {{ $student->student_code ?? 'N/A' }}
            </div>
            <div class="student-details">
                <strong>Ticket:</strong> {{ $enrollmentBatch->batch_code ?? str_pad($enrollmentBatch->id, 6, '0', STR_PAD_LEFT) }} //// <strong>Fecha:</strong> {{ $enrollmentBatch->created_at->format('d/m/Y') }}
            </div>
        </div>

        <!-- Tabla de talleres optimizada -->
        <table class="workshops-table">
            <thead>
                <tr>
                    <th class="taller-col">TALLER</th>
                    <th class="horario-col">HORARIO</th>
                    <th class="clases-col">N° CLASES</th>
                    <th class="fechas-col">FECHAS</th>
                    <th class="importe-col">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollmentBatch->enrollments as $enrollment)
                    @php
                        $workshop = $enrollment->instructorWorkshop;
                        $dayNames = [
                            1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES',
                            4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO',
                            7 => 'DOMINGO', 0 => 'DOMINGO'
                        ];
                        $dayInSpanish = $dayNames[$workshop->day_of_week] ?? 'N/A';
                        $startTime = \Carbon\Carbon::parse($workshop->start_time)->format('H:i');
                        $endTime = \Carbon\Carbon::parse($workshop->end_time)->format('H:i');

                        // Obtener las fechas de las clases específicas
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

                        // Si no hay clases específicas, mostrar las del taller
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
                        <td style="text-align: left; font-size: 9px; padding-left: 6px;">{{ strtoupper($workshop->workshop->name) }}</td>
                        <td class="compact-text" style="text-align: center;">{{ $dayInSpanish }}<br>{{ $startTime }}-{{ $endTime }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $enrollment->number_of_classes }}</td>
                        <td style="text-align: center;">
                            <div class="class-dates">
                                @if(!empty($classDates))
                                    {{ implode(' - ', $classDates) }}
                                @else
                                    Por definir
                                @endif
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: bold;">{{ number_format($enrollment->total_amount, 2) }}</td>
                    </tr>
                @endforeach

                <!-- Fila del total optimizada -->
                <tr class="total-row">
                    <td colspan="4" style="text-align: right; padding-right: 6px;">TOTAL:</td>
                    <td><strong>S/ {{ number_format($enrollmentBatch->total_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Footer con información de pago -->
        <div class="footer-section">
            <!-- Fila del monto pagado y vuelto (solo para pagos en efectivo) -->
            @if($enrollmentBatch->payment_method === 'cash' && $enrollmentBatch->amount_paid)
            <table width="100%" style="margin-bottom:4px; border-collapse:collapse; border:1.2px solid #000; background-color:#f9f9f9;">
                <tr>
                    <td style="font-weight:bold; font-size:11px; padding:4px 6px; text-align:left; border:none;">
                        MONTO PAGADO: S/ {{ number_format($enrollmentBatch->amount_paid, 2) }}
                    </td>
                    <td style="font-weight:bold; font-size:11px; padding:4px 6px; text-align:right; border:none;">
                        VUELTO: S/ {{ number_format($enrollmentBatch->change_amount ?? 0, 2) }}
                    </td>
                </tr>
            </table>
            @endif

            <!-- Fila del usuario y mes pagado -->
            <table width="100%" style="margin-bottom:5px; border-collapse:collapse; border:1.2px solid #000; background-color:#f9f9f9;">
                <tr>
                    <td style="font-weight:bold; font-size:11px; padding:4px 6px; text-align:left; border:none;">
                        USUARIO: {{ $created_by_user }}
                    </td>
                    <td style="font-weight:bold; font-size:11px; padding:4px 6px; text-align:right; border:none;">
                        MES PAGADO:
                        @if($enrollmentBatch->enrollments->first() && $enrollmentBatch->enrollments->first()->monthlyPeriod)
                            {{ strtoupper(\Carbon\Carbon::createFromDate($enrollmentBatch->enrollments->first()->monthlyPeriod->year, $enrollmentBatch->enrollments->first()->monthlyPeriod->month)->translatedFormat('M Y')) }}
                        @else
                            {{ strtoupper(\Carbon\Carbon::parse($enrollmentBatch->created_at)->translatedFormat('M Y')) }}
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Total en palabras -->
            <div class="total-words">
                <strong>SON:</strong> {{ $totalInWords ?? 'CANTIDAD EN PALABRAS' }}
            </div>
        </div>
    </div>
</body>
</html>
