<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones - {{ $period_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .summary-label {
            font-size: 10px;
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
            font-size: 9px;
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
        <h1>REPORTE DE INSCRIPCIONES POR MES</h1>
        <p><strong>Período: {{ $period_name }}</strong></p>
        <p>Generado el {{ $generated_at }}</p>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Documento</th>
                <th>Taller</th>
                <th>Instructor</th>
                <th>Fecha Inscripción</th>
                <th>N° Clases</th>
                <th>Monto</th>
                <th>Método Pago</th>
                <th>Modalidad</th>
                <th>N° Documento</th>
                <th>Cajero</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enrollment)
            <tr>
                <td>{{ $enrollment['student_name'] }}</td>
                <td>{{ $enrollment['student_document'] }}</td>
                <td>{{ $enrollment['workshop_name'] }}</td>
                <td>{{ $enrollment['instructor_name'] }}</td>
                <td>{{ $enrollment['enrollment_date'] }}</td>
                <td>{{ $enrollment['number_of_classes'] }}</td>
                <td>S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                <td>{{ $enrollment['payment_method'] }}</td>
                <td>{{ $enrollment['modality'] }}</td>
                <td>{{ $enrollment['payment_document'] }}</td>
                <td>{{ $enrollment['cashier_name'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
