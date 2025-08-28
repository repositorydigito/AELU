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
        <h1>REPORTE DE INSCRIPCIONES POR ALUMNO</h1>
        <p>{{ $student->first_names }} {{ $student->last_names }}</p>
    </div>

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
                <th>Documento</th>
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
</body>
</html>
