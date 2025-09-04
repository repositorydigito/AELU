<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pagos - {{ $instructor->first_names }} {{ $instructor->last_names }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            font-size: 18px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 10px;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE PAGOS POR PROFESOR</h1>
        <p>{{ $instructor->first_names }} {{ $instructor->last_names }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Taller</th>
                <th>Horario</th>
                <th>Período</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Fecha de Pago</th>
                <th>N° Documento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
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
