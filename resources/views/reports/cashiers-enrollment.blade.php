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
        .summary {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .summary-item {
            flex: 1;
        }
        .summary-item h3 {
            margin: 0;
            font-size: 14px;
            color: #333;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 9px;
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
            font-size: 8px;
            border: 1px solid #ddd;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 8px;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .workshops-list {
            font-size: 7px;
            line-height: 1.2;
        }
        .workshop-item {
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #eee;
        }
        .workshop-item:last-child {
            border-bottom: none;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR CAJERO</h1>
        <p><strong>Cajero:</strong> {{ $cashier_name }}</p>
        <p><strong>Período:</strong> {{ $date_from }} - {{ $date_to }}</p>
        <p><strong>Generado:</strong> {{ $generated_at }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <h3>{{ count($enrollments) }}</h3>
                <p>Total Lotes</p>
            </div>
            <div class="summary-item">
                <h3>{{ collect($enrollments)->sum('workshops_count') }}</h3>
                <p>Total Talleres</p>
            </div>
            <div class="summary-item">
                <h3>{{ collect($enrollments)->sum('total_classes') }}</h3>
                <p>Total Clases</p>
            </div>
            <div class="summary-item">
                <h3>S/ {{ number_format(collect($enrollments)->sum('total_amount'), 2) }}</h3>
                <p>Total Recaudado</p>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 12%;">Fecha Pago</th>
                <th style="width: 18%;">Estudiante</th>
                <th style="width: 25%;">Taller</th>
                <th style="width: 20%;">Instructor</th>
                <th style="width: 8%;">Clases</th>
                <th style="width: 10%;">Monto</th>
                <th style="width: 7%;">Método</th>
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
                    <strong>{{ $enrollment['workshop_name'] }}</strong>
                </td>
                <td>
                    {{ $enrollment['instructor_name'] }}
                </td>
                <td style="text-align: center;">{{ $enrollment['number_of_classes'] }}</td>
                <td style="text-align: right;"><strong>S/ {{ number_format($enrollment['total_amount'], 2) }}</strong></td>
                <td style="text-align: center;">{{ $enrollment['payment_method'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Gestión PAMA</p>
    </div>
</body>
</html>
