<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones por Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
            font-size: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background-color: #e0e0e0;
            color: #000;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            border: 1px solid #999;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 9px;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .table tr:nth-child(odd) {
            background-color: #fff;
        }
        .text-small {
            font-size: 8px;
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
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR CAJERO - GENERAL</h1>
        <p><strong>Período:</strong> {{ $date_from }} - {{ $date_to }}</p>
        <p><strong>Generado:</strong> {{ $generated_at }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 12%;">Cajero</th>
                <th style="width: 11%;">Fecha Pago</th>
                <th style="width: 18%;">Estudiante</th>
                <th style="width: 25%;">Talleres</th>
                <th style="width: 9%;">Monto</th>
                <th style="width: 8%;">Método</th>
                <th style="width: 8%;">Estado</th>
                <th style="width: 9%;">Nº Ticket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($all_enrollments as $enrollment)
            <tr>
                <td>{{ $enrollment['user_name'] }}</td>
                <td>
                    <span class="text-bold">{{ $enrollment['payment_registered_time'] }}</span><br>
                    <span class="text-small">Inscr: {{ $enrollment['enrollment_date'] }}</span>
                </td>
                <td>
                    <span class="text-bold">{{ $enrollment['student_name'] }}</span><br>
                    <span class="text-small">{{ $enrollment['student_code'] }}</span>
                </td>
                <td>
                    <span class="text-bold">{{ $enrollment['workshops_count'] }} taller(es)</span><br>
                    <span class="text-small">{{ Str::limit($enrollment['workshops_list'], 60) }}</span>
                </td>
                <td class="text-right text-bold">S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                <td class="text-center">{{ $enrollment['payment_method'] }}</td>
                <td class="text-center text-small">{{ $enrollment['payment_status'] }}</td>
                <td class="text-center text-small">{{ $enrollment['batch_code'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>