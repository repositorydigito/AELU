<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket de Inscripción - {{ $student->last_names ?? 'N/A'}} {{ $student->first_name ?? 'N/A' }}</title>
    <style>
        /* Estilos CSS para Dompdf */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin: 0;
            padding: 0;
        }
        .header h2 {
            color: #3498db; /* Azul Filament-like */
            font-size: 22px;
            margin-top: 5px;
            font-weight: normal;
        }
        .student-info {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .student-info p {
            margin: 5px 0;
            font-size: 15px;
        }
        .student-info strong {
            color: #555;
        }

        h3 {
            color: #2c3e50;
            font-size: 18px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .calendar-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed; /* Ayuda a que las columnas tengan ancho fijo */
        }
        .calendar-container th, .calendar-container td {
            border: 1px solid #e9ecef; /* Borde más suave */
            padding: 8px;
            text-align: center;
            vertical-align: top; /* Contenido arriba */
            min-height: 40px; /* Altura mínima para las celdas de tiempo */
        }
        .calendar-container th {
            background-color: #f8f9fa; /* Fondo claro para encabezados */
            color: #495057;
            font-weight: bold;
            font-size: 14px;
            padding: 10px;
        }
        .time-slot {
            width: 70px; /* Ancho fijo para la columna de hora */
            font-size: 0.9em;
            color: #6c757d;
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .class-block {
            background-color: #d4edda; /* Un verde suave */
            border: 1px solid #28a745; /* Verde más oscuro */
            color: #155724; /* Texto verde oscuro */
            padding: 6px;
            margin-bottom: 4px; /* Espacio entre bloques si hay varios en la misma celda */
            border-radius: 4px;
            font-size: 0.7em; /* Letra más pequeña para el bloque */
            line-height: 1.3;
            overflow: hidden; /* Asegura que el contenido no se desborde */
            word-wrap: break-word; /* Rompe palabras largas */
            text-align: left;
        }
        .no-classes {
            color: #adb5bd;
            font-style: italic;
            font-size: 0.8em;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ticket de Inscripción</h1>
    </div>    

    <h3>Calendario de clases inscritas ( {{ \Carbon\Carbon::createFromDate($enrollment->monthlyPeriod->year ?? 0, $enrollment->monthlyPeriod->month ?? 0)->translatedFormat('F') }} - {{ $enrollment->monthlyPeriod->year ?? 'N/A' }} )</h3>

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

    <div class="student-info">
        <p><strong>Alumno:</strong> {{ $student->last_names ?? 'N/A'}} {{ $student->first_names ?? 'N/A' }} - {{ $student->student_code ?? 'N/A' }}</p>
        <p><strong>Número de Documento:</strong> {{ $student->document_number ?? 'N/A' }}</p>
        {{-- <p><strong>Tipo de Inscripción:</strong> {{ $enrollment->enrollment_type === 'full_month' ? 'Mes completo' : 'Clases Específicas' }}</p> --}}
        {{-- <p><strong>Estado de Pago:</strong> {{ ucfirst($enrollment->payment_status) }}</p> --}}
        <p><strong>Monto Total:</strong> S/. {{ number_format($enrollment->total_amount, 2) }}</p>
    </div>

    <div class="footer">
        <p>Generado el {{ Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
        <p>Este es un documento generado automáticamente. Para cualquier consulta, contacte a la administración.</p>
    </div>
</body>
</html>