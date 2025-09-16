<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones - {{ $student->first_names }} {{ $student->last_names }}</title>
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
        .header .subtitle {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header .filter-info {
            margin: 10px 0;
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        .summary-info {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #333;
        }
        .summary-info .total-info {
            font-size: 11px;
            margin: 2px 0;
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
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE INSCRIPCIONES POR ALUMNO</h1>
        <p class="subtitle">{{ $student->first_names }} {{ $student->last_names }}</p>
        @if(isset($period_filter) && $period_filter)
            <p class="filter-info">Filtrado por período: {{ $period_filter }}</p>
        @else
            <p class="filter-info">Todas las inscripciones históricas</p>
        @endif
    </div>

    <div class="summary-info">
        <div class="total-info"><strong>Total de inscripciones:</strong> {{ count($enrollments) }}</div>
        @if(count($enrollments) > 0)
            <div class="total-info"><strong>Monto total:</strong> S/ {{ number_format(collect($enrollments)->sum('total_amount'), 2) }}</div>
            <div class="total-info"><strong>Total de clases:</strong> {{ collect($enrollments)->sum('number_of_classes') }}</div>
        @endif
        <div class="total-info"><strong>Generado el:</strong> {{ $generated_at }}</div>
    </div>

    @if(count($enrollments) > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Taller</th>
                <th>Instructor</th>
                <th>Período</th>
                <th>Fecha Inscripción</th>
                <th>N° Clases</th>
                <th>Monto Total</th>
                <th>Método de Pago</th>
                <th>Modalidad</th>
                <th>N° Ticket</th>
                <th>Cajero</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr>
                <td>{{ $enrollment['workshop_name'] }}</td>
                <td>{{ $enrollment['instructor_name'] }}</td>
                <td>{{ $enrollment['period_name'] }}</td>
                <td>{{ $enrollment['enrollment_date'] }}</td>
                <td>{{ $enrollment['number_of_classes'] }}</td>
                <td>S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                <td>{{ $enrollment['payment_method'] }}</td>
                <td>{{ $enrollment['modality'] ?? '' }}</td>
                <td>{{ $enrollment['payment_document'] ?? '' }}</td>
                <td>{{ $enrollment['cashier_name'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 40px; color: #666;">
        <h3>No hay inscripciones para mostrar</h3>
        @if(isset($period_filter) && $period_filter)
            <p>No se encontraron inscripciones para el período {{ $period_filter }}.</p>
        @else
            <p>Este alumno no tiene inscripciones registradas.</p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Sistema de Gestión de Inscripciones</p>
    </div>
</body>
</html>
