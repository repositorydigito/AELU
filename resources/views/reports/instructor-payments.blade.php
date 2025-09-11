<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title ?? 'Reporte de Pagos' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 16px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .summary {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 12px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 6px 4px;
            text-align: left;
            font-size: 8px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 8px;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report_title ?? 'REPORTE DE PAGOS' }}</h1>
        <p><strong>{{ $report_subtitle ?? '' }}</strong></p>
        <p>Generado el {{ $generated_at }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                @if($show_instructor_column ?? false)
                <th>Instructor</th>
                @endif
                <th>Taller</th>
                <th>Horario</th>
                <th>Período</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Fecha de Pago</th>
                <th>N° Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                @if($show_instructor_column ?? false)
                <td>{{ $payment['instructor_name'] }}</td>
                @endif
                <td>{{ $payment['workshop_name'] }}</td>
                <td>{{ $payment['workshop_schedule'] }}</td>
                <td>{{ $payment['period_name'] }}</td>
                <td>{{ $payment['payment_type'] }}</td>
                <td>S/ {{ number_format($payment['calculated_amount'], 2) }}</td>
                <td>{{ $payment['payment_date'] }}</td>
                <td>{{ $payment['document_number'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
