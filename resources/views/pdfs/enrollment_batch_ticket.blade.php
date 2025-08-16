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
            page-break-after: always;
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
            padding: 4px;
            text-align: center;
            font-size: 10px;
        }
        
        .workshops-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .workshops-table td {
            vertical-align: middle;
        }
        
        .taller-col { width: 35%; }
        .horario-col { width: 25%; }
        .modalidad-col { width: 20%; }
        .importe-col { width: 20%; }
        
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
        
        /* Estilos para el calendario */
        .calendar-section {
            margin-top: 20px;
        }
        
        .calendar-section h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .calendar-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        
        .calendar-container th, 
        .calendar-container td {
            border: 1px solid #e9ecef;
            padding: 8px;
            text-align: center;
            vertical-align: top;
            min-height: 40px;
        }
        
        .calendar-container th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            font-size: 14px;
            padding: 10px;
        }
        
        .time-slot {
            width: 70px;
            font-size: 0.9em;
            color: #6c757d;
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .class-block {
            background-color: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
            padding: 6px;
            margin-bottom: 4px;
            border-radius: 4px;
            font-size: 0.7em;
            line-height: 1.3;
            overflow: hidden;
            word-wrap: break-word;
            text-align: left;
        }
        
        .no-classes {
            color: #adb5bd;
            font-style: italic;
            font-size: 0.8em;
        }
        
        .workshops-summary {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 8px;
        }
        
        .workshop-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #fff;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        
        .workshop-item:last-child {
            margin-bottom: 0;
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

        {{-- <div class="info-row">
            <div class="info-label">APELLIDOS Y NOMBRES</div>
            <div class="info-value">{{ $student->last_names ?? 'N/A' }}, {{ $student->first_names ?? 'N/A' }}</div>
        </div> --}}

        <table class="workshops-table">
            <thead>
                <tr>
                    <th class="taller-col">TALLER</th>
                    <th class="horario-col">HORARIO</th>
                    <th class="modalidad-col">N° CLASES</th>
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
                    @endphp
                    <tr>
                        <td style="text-align: left;">{{ strtoupper($workshop->workshop->name) }}</td>
                        <td>{{ $dayInSpanish }}<br>{{ $startTime }} - {{ $endTime }}</td>
                        <td>{{ $enrollment->number_of_classes }}</td>
                        <td>{{ number_format($enrollment->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                
                <!-- Fila del total -->
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding-right: 10px;">IMPORTE TOTAL:</td>
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
    
    <!-- CALENDARIO - SEGUNDA PÁGINA -->
    {{-- <div class="calendar-section">        

        <h3>Calendario de clases inscritas</h3>

        <table class="calendar-container">
            <thead>
                <tr>
                    <th class="time-slot">Hora</th>
                    @foreach($daysOfWeek as $day)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $hour)
                    <tr>
                        <td class="time-slot">{{ $hour }}:00</td>
                        @foreach($daysOfWeek as $day)
                            <td>
                                @php
                                    $classesInSlot = $calendarData[$day][$hour] ?? [];
                                    $hasClassesInSlot = !empty($classesInSlot);
                                @endphp

                                @if($hasClassesInSlot)
                                    @foreach($classesInSlot as $classDetail)
                                        <div class="class-block">
                                            {{ \Carbon\Carbon::parse($classDetail['class_date'])->format('d/m/y') }}<br>                                        
                                            {{ $classDetail['workshop_name'] }}
                                        </div>
                                    @endforeach
                                @else
                                    <span class="no-classes">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($enrollmentBatch->notes)
            <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                <strong>Notas:</strong><br>
                {{ $enrollmentBatch->notes }}
            </div>
        @endif
    </div> --}}
</body>
</html>