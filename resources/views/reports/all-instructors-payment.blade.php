<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte General de Pago de Profesores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
            margin: 20px;
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
            text-transform: uppercase;
        }
        .header p {
            margin: 3px 0;
            font-size: 11px;
        }

        .summary-box {
            border: 1px solid #000;
            padding: 8px 10px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .summary-box span {
            margin-right: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background-color: #e0e0e0;
            color: #000;
            padding: 8px 5px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #666;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #999;
            padding: 6px 5px;
            font-size: 11px;
            vertical-align: middle;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .table tbody tr:nth-child(odd) {
            background-color: #fff;
        }
        .table tfoot tr {
            background-color: #e0e0e0;
            font-weight: bold;
            border-top: 2px solid #000;
        }
        .table tfoot td {
            padding: 8px 5px;
            font-size: 12px;
            border: 1px solid #666;
        }
        .text-small {
            font-size: 9px;
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
        <h1>Reporte General de Pago de Profesores</h1>
        <p><strong>Período:</strong> {{ $monthly_period }} | <strong>Generado:</strong> {{ $generated_at }}</p>
    </div>

    <div class="summary-box">
        <span><strong>Total Por Horas:</strong> S/ {{ number_format($total_amount, 2) }}</span>
        <span><strong>Total Voluntarios:</strong> S/ {{ number_format(collect($all_payments)->where('modality', 'Voluntario')->sum('amount'), 2) }}</span>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 14%;">Profesor</th>
                <th style="width: 15%;">Taller</th>
                <th style="width: 12%;">Horario</th>
                <th style="width: 7%;">Tarifa Mensual</th>
                <th style="width: 6%;">Inscritos</th>
                <th style="width: 8%;">Modalidad</th>
                <th style="width: 5%;">Horas</th>
                <th style="width: 8%;">Tarifa o %</th>
                <th style="width: 8%;" class="text-right">Monto</th>
                <th style="width: 7%;">Estado</th>
                <th style="width: 10%;">N° Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($all_payments as $payment)
            <tr>
                <td>
                    <span class="text-bold">{{ $payment['instructor_name'] }}</span><br>
                </td>
                <td>{{ $payment['workshop_name'] }}</td>
                <td class="text-small">{{ $payment['schedule'] }}</td>
                <td class="text-center">S/ {{ number_format($payment['standard_monthly_fee'] ?? 0, 2) }}</td>
                <td class="text-center">{{ $payment['total_students'] ?? 0 }}</td>
                <td class="text-center">{{ $payment['modality'] }}</td>
                <td class="text-center">{{ number_format($payment['hours_worked'], 1) }}</td>
                <td class="text-center">
                    @if($payment['modality'] === 'Por Horas')
                        S/ {{ number_format($payment['hourly_rate'], 2) }}
                    @else
                        {{ number_format($payment['hourly_rate'], 0) }}%
                    @endif
                </td>
                <td class="text-right text-bold">S/ {{ number_format($payment['amount'], 2) }}</td>
                <td class="text-center">{{ $payment['payment_status'] }}</td>
                <td class="text-center text-small">{{ $payment['document_number'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format($total_amount + collect($all_payments)->where('modality', 'Voluntario')->sum('amount'), 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
