<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex por Profesor - {{ $instructor_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #333;
            margin: 10px;
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
            font-weight: bold;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            border: 1px solid #333;
            padding: 4px 6px;
            font-size: 10px;
            vertical-align: middle;
        }
        .info-label {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 15%;
        }
        .info-value {
            width: 35%;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th {
            background-color: #f5f5f5;
            color: #333;
            padding: 4px 3px;
            text-align: center;
            font-size: 10px;
            border: 1px solid #333;
            font-weight: bold;
        }
        .table td {
            border: 1px solid #333;
            padding: 3px;
            font-size: 10px;
            text-align: center;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-left {
            text-align: left !important;
        }
        .text-right {
            text-align: right !important;
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
        <h1>KARDEX POR PROFESOR</h1>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell info-label">Nombres:</div>
                <div class="info-cell info-value">{{ $instructor_name }}</div>
                <div class="info-cell info-label">Taller</div>
                <div class="info-cell info-value">{{ $workshop_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Modalidad</div>
                <div class="info-cell info-value">{{ $workshop_modality }}</div>
                <div class="info-cell info-label">Horario</div>
                <div class="info-cell info-value">{{ $workshop_schedule }}</div>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%;">Fecha</th>
                <th style="width: 8%;">Hora</th>
                <th style="width: 12%;">N° Ticket</th>
                <th style="width: 10%;">Código de socio</th>
                <th style="width: 25%;">Apellidos y nombres alumno</th>
                <th style="width: 8%;">Condición</th>
                <th style="width: 6%;">Moneda</th>
                <th style="width: 8%;">Importe</th>
                <th style="width: 5%;">Cajero</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr>
                <td>{{ $enrollment['fecha'] }}</td>
                <td>{{ $enrollment['hora'] }}</td>
                <td>{{ $enrollment['numero_documento'] }}</td>
                <td>{{ $enrollment['codigo_socio'] }}</td>
                <td class="text-left">{{ $enrollment['apellidos_nombres'] }}</td>
                <td>{{ $enrollment['condicion'] }}</td>
                <td>{{ $enrollment['moneda'] }}</td>
                <td class="text-right">{{ number_format($enrollment['importe'], 2) }}</td>
                <td>{{ $enrollment['cajero'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
