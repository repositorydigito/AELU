<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual de Inscripciones - {{ $period_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .header h2 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 15px 0 8px 0;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }

        th {
            background-color: #f0f0f0;
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
        }

        td {
            border: 1px solid #333;
            padding: 4px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-table {
            margin-top: 20px;
        }

        .summary-table th {
            background-color: #e0e0e0;
            font-size: 9px;
        }

        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .footer {
            text-align: right;
            margin-top: 30px;
            font-size: 9px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PROGRAMA ADULTO MAYOR AELU (PAMA)</h1>
        <h2>REPORTE MENSUAL DE INSTRUCTORES - {{ strtoupper($period_name) }}</h2>
    </div>

    @if(!empty($volunteer_workshops))
        <div class="section-title">VOLUNTARIOS</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">TALLER</th>
                    <th style="width: 20%;">INSTRUCTOR</th>
                    <th style="width: 12%;">HORARIO</th>
                    <th style="width: 10%;">MODALIDAD</th>
                    <th style="width: 8%;">TOTAL INSCRIPCIONES</th>
                    <th style="width: 8%;">TARIFA</th>
                    <th style="width: 10%;">INGRESO TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($volunteer_workshops as $workshop)
                    <tr>
                        <td>{{ $workshop['taller'] }}</td>
                        <td>{{ strtoupper($workshop['instructor']) }}</td>
                        <td class="text-center">{{ $workshop['horario'] }}</td>
                        <td class="text-center">{{ ucfirst($workshop['modalidad']) }}</td>
                        <td class="text-center">{{ $workshop['inscripciones'] }}</td>
                        <td class="text-right">{{ number_format($workshop['tarifa'], 2) }}</td>
                        <td class="text-right">{{ number_format($workshop['total_recaudado'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>SUBTOTAL VOLUNTARIOS:</strong></td>
                    <td class="text-center"><strong>{{ $summary['volunteer_total_enrollments'] }}</strong></td>
                    <td></td>
                    <td class="text-right"><strong>{{ number_format($summary['volunteer_total_amount'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endif

    @if(!empty($hourly_workshops))
        @if(!empty($volunteer_workshops))
            <div style="margin-top: 25px;"></div>
        @endif

        <div class="section-title">PAGO POR HORAS</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">TALLER</th>
                    <th style="width: 20%;">INSTRUCTOR</th>
                    <th style="width: 12%;">HORARIO</th>
                    <th style="width: 10%;">MODALIDAD</th>
                    <th style="width: 8%;">TOTAL INSCRIPCIONES</th>
                    <th style="width: 8%;">TARIFA</th>
                    <th style="width: 10%;">INGRESO TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hourly_workshops as $workshop)
                    <tr>
                        <td>{{ $workshop['taller'] }}</td>
                        <td>{{ strtoupper($workshop['instructor']) }}</td>
                        <td class="text-center">{{ $workshop['horario'] }}</td>
                        <td class="text-center">{{ ucfirst($workshop['modalidad']) }}</td>
                        <td class="text-center">{{ $workshop['inscripciones'] }}</td>
                        <td class="text-right">{{ number_format($workshop['tarifa'], 2) }}</td>
                        <td class="text-right">{{ number_format($workshop['total_recaudado'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>SUBTOTAL POR HORAS:</strong></td>
                    <td class="text-center"><strong>{{ $summary['hourly_total_enrollments'] }}</strong></td>
                    <td></td>
                    <td class="text-right"><strong>{{ number_format($summary['hourly_total_amount'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- Resumen final -->
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 50%;">DESCRIPCIÓN</th>
                <th style="width: 25%;">TOTAL INSCRIPCIONES</th>
                <th style="width: 25%;">INGRESO TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($volunteer_workshops))
                <tr>
                    <td><strong>VOLUNTARIOS</strong></td>
                    <td class="text-center">{{ $summary['volunteer_total_enrollments'] }}</td>
                    <td class="text-right">{{ number_format($summary['volunteer_total_amount'], 2) }}</td>
                </tr>
            @endif
            @if(!empty($hourly_workshops))
                <tr>
                    <td><strong>POR HORAS</strong></td>
                    <td class="text-center">{{ $summary['hourly_total_enrollments'] }}</td>
                    <td class="text-right">{{ number_format($summary['hourly_total_amount'], 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>TOTALES</strong></td>
                <td class="text-center"><strong>{{ $summary['grand_total_enrollments'] }}</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['grand_total_amount'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
