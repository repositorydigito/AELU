<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones por Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 16px;
        }
        .overall-summary {
            background-color: #e3f2fd;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #90caf9;
        }
        .overall-summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .overall-summary-row {
            display: table-row;
        }
        .overall-summary-cell {
            display: table-cell;
            text-align: center;
            padding: 4px;
            font-size: 8px;
        }
        .overall-summary-cell strong {
            display: block;
            font-size: 12px;
            margin-top: 2px;
            color: #1976d2;
        }
        .total-section {
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #90caf9;
        }
        .total-section .label {
            font-size: 8px;
            color: #666;
        }
        .total-section .amount {
            font-size: 16px;
            font-weight: bold;
            color: #1976d2;
            margin-top: 2px;
        }
        .user-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .user-header {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 8px;
            border-left: 4px solid #1976d2;
        }
        .user-header h2 {
            margin: 0;
            font-size: 12px;
            color: #333;
        }
        .user-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 4px;
            font-size: 8px;
        }
        .user-summary span {
            margin-right: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 5px 3px;
            text-align: left;
            font-size: 7px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 3px;
            font-size: 7px;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR USUARIO</h1>
        <p><strong>Período:</strong> {{ $date_from }} - {{ $date_to }}</p>
        <p><strong>Generado:</strong> {{ $generated_at }}</p>
    </div>

    <div class="overall-summary">
        <div class="overall-summary-grid">
            <div class="overall-summary-row">
                <div class="overall-summary-cell">
                    <span>Total Usuarios</span>
                    <strong>{{ $overall_summary['total_users'] }}</strong>
                </div>
                <div class="overall-summary-cell">
                    <span>Total Inscripciones</span>
                    <strong>{{ $overall_summary['total_enrollments'] }}</strong>
                </div>
                <div class="overall-summary-cell">
                    <span>Efectivo</span>
                    <strong>{{ $overall_summary['cash_count'] }}</strong>
                    <span style="font-size: 7px;">(S/ {{ number_format($overall_summary['cash_amount'], 2) }})</span>
                </div>
                <div class="overall-summary-cell">
                    <span>Link</span>
                    <strong>{{ $overall_summary['link_count'] }}</strong>
                    <span style="font-size: 7px;">(S/ {{ number_format($overall_summary['link_amount'], 2) }})</span>
                </div>
            </div>
        </div>
        <div class="total-section">
            <div class="label">Monto Total</div>
            <div class="amount">S/ {{ number_format($overall_summary['total_amount'], 2) }}</div>
        </div>
    </div>

    @foreach($users_enrollments as $index => $userData)
    <div class="user-section">
        <div class="user-header">
            <h2>{{ $userData['user_name'] }}</h2>
            <div class="user-summary">
                <span><strong>{{ $userData['summary']['total_count'] }}</strong> inscripciones</span>
                <span>{{ $userData['summary']['cash_count'] }} Efectivo (S/ {{ number_format($userData['summary']['cash_amount'], 2) }})</span>
                <span>{{ $userData['summary']['link_count'] }} Link (S/ {{ number_format($userData['summary']['link_amount'], 2) }})</span>
                <span><strong>Total: S/ {{ number_format($userData['summary']['total_amount'], 2) }}</strong></span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 13%;">Fecha Pago</th>
                    <th style="width: 20%;">Estudiante</th>
                    <th style="width: 26%;">Talleres</th>
                    <th style="width: 10%;">Monto</th>
                    <th style="width: 9%;">Método</th>
                    <th style="width: 8%;">Estado</th>
                    <th style="width: 14%;">Nº Ticket</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userData['enrollments'] as $enrollment)
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
                        <small>{{ Str::limit($enrollment['workshops_list'], 55) }}</small>
                    </td>
                    <td style="text-align: right;"><strong>S/ {{ number_format($enrollment['total_amount'], 2) }}</strong></td>
                    <td style="text-align: center;">{{ $enrollment['payment_method'] }}</td>
                    <td style="text-align: center; font-size: 6px;">
                        {{ $enrollment['payment_status'] }}
                    </td>
                    <td style="text-align: center; font-size: 6px;">{{ $enrollment['batch_code'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($index < count($users_enrollments) - 1 && ($index + 1) % 3 === 0)
    <div class="page-break"></div>
    @endif
    @endforeach

</body>
</html>
