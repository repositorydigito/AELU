<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones por Horario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #000;
            margin: 0 0 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 15px;
        }
        .info-section {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #666;
        }
        .info-section h2 {
            margin: 0 0 5px 0;
            font-size: 13px;
            color: #000;
            font-weight: bold;
        }
        .info-section p {
            margin: 2px 0;
            font-size: 9px;
            color: #333;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background-color: #e0e0e0;
            color: #000;
            padding: 5px 3px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #999;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            font-size: 11px;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .table tr:nth-child(odd) {
            background-color: #fff;
        }
        .text-small {
            font-size: 7px;
            color: #555;
        }
        .text-bold {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR HORARIO</h1>
        <p><strong>Periodo:</strong> {{ $period_name }} ({{ $period_dates }})</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 16%;">Estudiante</th>
                <th style="width: 9%;">F. Inscr.</th>
                <th style="width: 12%;">F. Pago</th>
                <th style="width: 6%;">Nº Clases</th>
                <th style="width: 9%;">Monto</th>
                <th style="width: 9%;">Método</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 13%;">Cajero</th>
                <th style="width: 10%;">Código Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr>
                <td>
                    <span class="text-bold">{{ $enrollment['student_name'] }}</span><br>
                    <span class="text-small">{{ $enrollment['student_code'] }}</span>
                </td>
                <td class="text-center">{{ $enrollment['enrollment_date'] }}</td>
                <td class="text-center">{{ $enrollment['payment_registered_time'] }}</td>
                <td class="text-center text-bold">{{ $enrollment['number_of_classes'] }}</td>
                <td class="text-right text-bold">S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                <td class="text-center">{{ $enrollment['payment_method'] }}</td>
                <td class="text-center text-small">{{ $enrollment['payment_status'] }}</td>
                <td>{{ $enrollment['user_name'] }}</td>
                <td class="text-center text-small">{{ $enrollment['ticket_code'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
