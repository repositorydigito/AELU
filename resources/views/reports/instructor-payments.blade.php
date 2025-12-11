<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report_title ?? 'Reporte de Pagos' }}</title>
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
            font-size: 10px;
            border: 1px solid #666;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #999;
            padding: 6px 5px;
            font-size: 10px;
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
        <h1>{{ $report_title ?? 'REPORTE DE PAGOS DE INSTRUCTOR' }}</h1>
        <p><strong>{{ $report_subtitle ?? '' }}</strong></p>
        <p>Generado el {{ $generated_at }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                @if($show_instructor_column ?? false)
                <th style="width: 12%;">Instructor</th>
                @endif
                <th style="width: 15%;">Taller</th>
                <th style="width: 12%;">Horario</th>
                <th style="width: 8%;">Tarifa Mensual</th>
                <th style="width: 6%;">Inscritos</th>
                <th style="width: 9%;">Período</th>
                <th style="width: 8%;">Tipo</th>
                <th style="width: 6%;">Horas</th>
                <th style="width: 8%;">Tarifa o %</th>
                <th style="width: 9%;" class="text-right">Monto</th>
                <th style="width: 9%;">Fecha de Pago</th>
                <th style="width: 8%;">N° Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                @if($show_instructor_column ?? false)
                <td>{{ $payment['instructor_name'] }}</td>
                @endif
                <td>{{ $payment['workshop_name'] }}</td>
                <td class="text-small">{{ $payment['workshop_schedule'] }}</td>
                <td class="text-center">S/ {{ number_format($payment['standard_monthly_fee'], 2) }}</td>
                <td class="text-center">{{ $payment['total_students'] }}</td>
                <td>{{ $payment['period_name'] }}</td>
                <td class="text-center">{{ $payment['payment_type'] }}</td>
                <td class="text-center">{{ number_format($payment['total_hours'], 1) }}</td>
                <td class="text-center">
                    @if($payment['payment_type'] === 'Por Horas')
                        S/ {{ number_format($payment['rate_or_percentage_value'], 2) }}
                    @else
                        {{ number_format($payment['rate_or_percentage_value'], 0) }}%
                    @endif
                </td>
                <td class="text-right text-bold">S/ {{ number_format($payment['calculated_amount'], 2) }}</td>
                <td class="text-center">{{ $payment['payment_date'] }}</td>
                <td class="text-center text-small">{{ $payment['document_number'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ ($show_instructor_column ?? false) ? '9' : '8' }}" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>S/ {{ number_format(collect($payments)->sum('calculated_amount'), 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
