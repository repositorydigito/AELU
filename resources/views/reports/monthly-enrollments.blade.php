<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tickets - {{ $period_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 16px;
        }
        .summary {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .summary-label {
            font-size: 10px;
            color: #666;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 6px 4px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 10px;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        p {
            font-size: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR MES</h1>
        <p><strong>Período: {{ $period_name }}</strong></p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Código</th>
                <th>Fecha Inscripción</th>
                <th>Monto</th>
                <th>Método Pago</th>
                <th>N° Ticket</th>
                <th>Estado</th>
                <th>Cajero</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>{{ $ticket['student_name'] }}</td>
                <td>{{ $ticket['student_code'] }}</td>
                <td>{{ $ticket['enrollment_date'] }}</td>
                <td>S/ {{ number_format($ticket['total_amount'], 2) }}</td>
                <td>{{ $ticket['payment_method'] }}</td>
                <td>{{ $ticket['ticket_code'] ?? '' }}</td>
                <td>{{ $ticket['ticket_status'] ?? '' }}</td>
                <td>{{ $ticket['cashier_name'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
