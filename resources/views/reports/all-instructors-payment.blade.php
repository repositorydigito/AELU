<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte General de Pago de Profesores</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 20px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header h1 { margin: 0 0 4px 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; }

        .summary-box { border: 1px solid #999; padding: 6px 10px; margin-bottom: 15px; font-size: 10px; }
        .summary-box span { margin-right: 30px; }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            margin: 16px 0 4px 0;
            border-left: 4px solid #555;
            background-color: #e5e7eb;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th {
            background-color: #e5e7eb;
            padding: 5px 4px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #aaa;
            font-weight: bold;
        }
        td { border: 1px solid #ccc; padding: 4px; font-size: 10px; vertical-align: middle; }

        .instructor-row td {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #555;
            border-bottom: 1px solid #555;
        }

        .subtotal-row td {
            background-color: #f3f4f6;
            font-weight: bold;
            border-top: 1px solid #aaa;
        }

        tfoot tr td {
            background-color: #d1d5db;
            font-weight: bold;
            font-size: 11px;
            border-top: 2px solid #555;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-bold   { font-weight: bold; }

        .grand-total {
            border: 2px solid #000;
            padding: 6px 10px;
            margin-top: 12px;
            font-size: 12px;
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte General de Pago de Profesores</h1>
        <p><strong>Período:</strong> {{ $monthly_period }} &nbsp;|&nbsp; <strong>Generado:</strong> {{ $generated_at }}</p>
    </div>

    <div class="summary-box">
        <span><strong>Total Voluntarios:</strong> S/ {{ number_format($total_amount['volunteer'], 2) }}</span>
        <span><strong>Total Por Horas:</strong> S/ {{ number_format($total_amount['hourly'], 2) }}</span>
        <span><strong>Total General:</strong> S/ {{ number_format($total_amount['grand_total'], 2) }}</span>
    </div>

    {{-- SECCIÓN VOLUNTARIOS --}}
    @if(!empty($grouped_payments['volunteer']))
    <div class="section-title">Voluntarios</div>
    <table>
        <thead>
            <tr>
                <th style="width:18%">Taller</th>
                <th style="width:15%">Horario</th>
                <th style="width:7%" class="text-center">Inscritos</th>
                <th style="width:8%" class="text-center">Cant. por categoría</th>
                <th style="width:11%" class="text-right">Monto por categoría</th>
                <th style="width:7%" class="text-right">Tarifa</th>
                <th style="width:10%" class="text-right">Ingresos del Taller</th>
                <th style="width:6%" class="text-center">%</th>
                <th style="width:11%" class="text-right">Monto a Pagar</th>
                <th style="width:7%" class="text-center">Recibo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped_payments['volunteer'] as $instructor)
                <tr class="instructor-row">
                    <td colspan="10">{{ $instructor['instructor_name'] }}</td>
                </tr>
                @foreach($instructor['workshops'] as $workshop)
                @php
                    $breakdown = $workshop['students_by_classes'] ?? [];
                    $parts = [];
                    foreach ($breakdown as $n => $count) {
                        if ($count > 0) {
                            $parts[] = ($n > 0 ? $n . 'c' : '?c') . ':' . $count;
                        }
                    }

                    $categoryBreakdown = $workshop['students_by_category'] ?? [];
                    $categoryAmounts = $workshop['unit_amount_by_category'] ?? [];
                    $categoryCounts = [];
                    $categoryAmountValues = [];
                    foreach ($categoryBreakdown as $category => $count) {
                        if ($count > 0) {
                            $categoryCounts[] = $count;
                            $categoryAmountValues[] = 'S/ ' . number_format((float) ($categoryAmounts[$category] ?? 0), 2);
                        }
                    }
                @endphp
                <tr>
                    <td style="padding-left:14px">{{ $workshop['workshop_name'] }}</td>
                    <td>{{ $workshop['schedule'] }}@if(!empty($workshop['modality']))<br><small>{{ $workshop['modality'] }}</small>@endif</td>
                    <td class="text-center">
                        {{ $workshop['total_students'] }}
                        @if(!empty($parts))
                            <br><small style="font-size:8px;color:#555">{{ implode(' | ', $parts) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(!empty($categoryCounts))
                            <small style="font-size:8px;color:#555">{!! implode('<br>', $categoryCounts) !!}</small>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        @if(!empty($categoryAmountValues))
                            <small style="font-size:8px;color:#555">{!! implode('<br>', $categoryAmountValues) !!}</small>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">S/ {{ number_format($workshop['standard_fee'] ?? 0, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($workshop['monthly_revenue'], 2) }}</td>
                    <td class="text-center">{{ number_format($workshop['volunteer_percentage'], 0) }}%</td>
                    <td class="text-right text-bold">S/ {{ number_format($workshop['amount'], 2) }}</td>
                    <td class="text-center">{{ $workshop['document_number'] ?? '—' }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="8" class="text-right">Subtotal {{ $instructor['instructor_name'] }}:</td>
                    <td class="text-right">S/ {{ number_format($instructor['subtotal'], 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right">TOTAL VOLUNTARIOS:</td>
                <td class="text-right">S/ {{ number_format($total_amount['volunteer'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- SECCIÓN POR HORAS --}}
    @if(!empty($grouped_payments['hourly']))
    <div class="section-title">Por Horas</div>
    <table>
        <thead>
            <tr>
                <th style="width:18%">Taller</th>
                <th style="width:15%">Horario</th>
                <th style="width:7%" class="text-center">Inscritos</th>
                <th style="width:8%" class="text-center">Cant. por categoría</th>
                <th style="width:11%" class="text-right">Monto por categoría</th>
                <th style="width:7%" class="text-right">Tarifa</th>
                <th style="width:10%" class="text-center">Horas</th>
                <th style="width:10%" class="text-center">Tarifa/hora</th>
                <th style="width:10%" class="text-right">Monto a Pagar</th>
                <th style="width:7%" class="text-center">Recibo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grouped_payments['hourly'] as $instructor)
                <tr class="instructor-row">
                    <td colspan="10">{{ $instructor['instructor_name'] }}</td>
                </tr>
                @foreach($instructor['workshops'] as $workshop)
                @php
                    $breakdown = $workshop['students_by_classes'] ?? [];
                    $parts = [];
                    foreach ($breakdown as $n => $count) {
                        if ($count > 0) {
                            $parts[] = ($n > 0 ? $n . 'c' : '?c') . ':' . $count;
                        }
                    }

                    $categoryBreakdown = $workshop['students_by_category'] ?? [];
                    $categoryAmounts = $workshop['unit_amount_by_category'] ?? [];
                    $categoryCounts = [];
                    $categoryAmountValues = [];
                    foreach ($categoryBreakdown as $category => $count) {
                        if ($count > 0) {
                            $categoryCounts[] = $count;
                            $categoryAmountValues[] = 'S/ ' . number_format((float) ($categoryAmounts[$category] ?? 0), 2);
                        }
                    }
                @endphp
                <tr>
                    <td style="padding-left:14px">{{ $workshop['workshop_name'] }}</td>
                    <td>{{ $workshop['schedule'] }}@if(!empty($workshop['modality']))<br><small>{{ $workshop['modality'] }}</small>@endif</td>
                    <td class="text-center">
                        {{ $workshop['total_students'] }}
                        @if(!empty($parts))
                            <br><small style="font-size:8px;color:#555">{{ implode(' | ', $parts) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(!empty($categoryCounts))
                            <small style="font-size:8px;color:#555">{!! implode('<br>', $categoryCounts) !!}</small>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        @if(!empty($categoryAmountValues))
                            <small style="font-size:8px;color:#555">{!! implode('<br>', $categoryAmountValues) !!}</small>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">S/ {{ number_format($workshop['standard_fee'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ number_format($workshop['hours_worked'], 1) }}</td>
                    <td class="text-center">S/ {{ number_format($workshop['hourly_rate'], 2) }}</td>
                    <td class="text-right text-bold">S/ {{ number_format($workshop['amount'], 2) }}</td>
                    <td class="text-center">{{ $workshop['document_number'] ?? '—' }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="8" class="text-right">Subtotal {{ $instructor['instructor_name'] }}:</td>
                    <td class="text-right">S/ {{ number_format($instructor['subtotal'], 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right">TOTAL POR HORAS:</td>
                <td class="text-right">S/ {{ number_format($total_amount['hourly'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="grand-total">
        TOTAL GENERAL A PAGAR: &nbsp; S/ {{ number_format($total_amount['grand_total'], 2) }}
    </div>

</body>
</html>
