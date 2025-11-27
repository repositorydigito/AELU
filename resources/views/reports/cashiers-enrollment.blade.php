<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones por Cajero - {{ $cashier_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
        .header p {
            font-size: 15px;
        }
        .summary {
            background-color: #f5f5f5;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .horizontal-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: center;
        }
        .summary-text {
            flex: 1;
            font-size: 11px;
            color: #333;
        }
        .summary-separator {
            margin: 0 8px;
            color: #999;
            font-weight: bold;
        }
        .total-text {
            font-weight: bold;
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
            font-size: 11px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 11px;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR CAJERO</h1>
        <p><strong>Cajero:</strong> {{ $cashier_name }}</p>
        <p><strong>Período:</strong> {{ $date_from }} - {{ $date_to }}</p>
    </div>

    <div class="summary">
        <div class="horizontal-summary">
            <span class="summary-text">{{ $payment_summary['total_enrollments'] }} Inscripciones en total</span>
            <span class="summary-separator">|</span>
            <span class="summary-text cash-text">{{ $payment_summary['cash_count'] }} Efectivo (S/ {{ number_format($payment_summary['cash_amount'], 2) }})</span>
            <span class="summary-separator">|</span>
            <span class="summary-text link-text">{{ $payment_summary['link_count'] }} Link (S/ {{ number_format($payment_summary['link_amount'], 2) }})</span>
            <span class="summary-separator">|</span>
            <span class="summary-text">{{ $payment_summary['inscribed_count'] }} Inscritos</span>
            <span class="summary-separator">|</span>
            <span class="summary-text">{{ $payment_summary['cancelled_count'] }} Anulados</span>
            <span class="summary-separator">|</span>
            <span class="summary-text total-text"><strong>Total: S/ {{ number_format($payment_summary['total_amount'], 2) }}</strong></span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 14%;">Fecha Pago</th>
                <th style="width: 20%;">Estudiante</th>
                <th style="width: 25%;">Talleres</th>
                <th style="width: 10%;">Monto</th>
                <th style="width: 9%;">Método</th>
                <th style="width: 8%;">Estado</th>
                <th style="width: 14%;">Nº Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr>
                <td>
                    <strong>{{ $enrollment['payment_registered_time'] }}</strong><br>
                    <small>Inscr: {{ $enrollment['enrollment_date'] }}</small>
                </td>
                <td>
                    <strong>{{ $enrollment['student_name'] }}</strong><br>
                    <small>{{ $enrollment['student_code'] }}</small>
                </td>
                <td>
                    <strong>{{ $enrollment['workshops_count'] }} taller(es)</strong><br>
                    <small>{{ Str::limit($enrollment['workshops_list'], 60) }}</small>
                </td>
                <td style="text-align: right;"><strong>S/ {{ number_format($enrollment['total_amount'], 2) }}</strong></td>
                <td style="text-align: center;">{{ $enrollment['payment_method'] }}</td>
                <td style="text-align: center; font-size: 11px;">
                    {{ $enrollment['payment_status'] }}
                </td>
                <td style="text-align: center; font-size: 11px;">{{ $enrollment['ticket_code'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
