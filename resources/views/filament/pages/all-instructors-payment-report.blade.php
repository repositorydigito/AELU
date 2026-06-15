<x-filament-panels::page>
    <style>
        .ipr-row-odd {
            background-color: #f0fdf4;
        }

        .ipr-row-even {
            background-color: transparent;
        }

        .ipr-instructor-header {
            background-color: #bbf7d0;
        }

        .ipr-instructor-header td {
            color: #166534;
            font-weight: 600;
        }
    </style>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtro de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione el período mensual para ver todos los pagos de profesores
            </x-slot>
            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Resumen Total -->
        <x-filament::section>
            <x-slot name="heading">
                Resumen de Pagos
            </x-slot>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Voluntarios</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            S/ {{ number_format($totalAmount['volunteer'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Por Horas</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            S/ {{ number_format($totalAmount['hourly'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total General</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            S/ {{ number_format($totalAmount['grand_total'] ?? 0, 2) }}
                        </p>
                    </div>
                </div>
            </x-filament::section>

        @if (!empty($allInstructorPayments))
            <!-- Sección: Voluntarios -->
            @if (!empty($allInstructorPayments['volunteer']))
                <h4 class="text-lg mt-2 font-medium text-gray-900 dark:text-white">
                    Voluntarios
                </h4>
                <div class="relative rounded-lg mt-2 border border-gray-200 dark:border-gray-700">
                    <div class="overflow-y-auto">
                        <table class="w-full table-fixed text-sm">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th
                                        class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Taller</th>
                                    <th
                                        class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Horario</th>
                                    <th
                                        class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Inscritos</th>
                                    <th
                                        class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Tarifa Mensual</th>
                                    <th
                                        class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Ingresos del Taller</th>
                                    <th
                                        class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Monto a Pagar</th>
                                    <th
                                        class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Monto a Favor</th>
                                    <th
                                        class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Estado</th>
                                    <th
                                        class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                                        Recibo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                {{-- ============================================================ --}}
                                {{-- SECCIÓN: VOLUNTARIOS                                        --}}
                                {{-- Estructura por instructor:                                  --}}
                                {{--   1. Fila agrupadora (nombre del instructor)                --}}
                                {{--   2. Filas de talleres (taller, horario, inscritos, %)      --}}
                                {{--   3. Fila subtotal del instructor                           --}}
                                {{-- ============================================================ --}}
                                @foreach ($allInstructorPayments['volunteer'] as $instructor)
                                    {{-- 1. Fila agrupadora del instructor --}}
                                    <tr class="ipr-instructor-header">
                                        <td colspan="9" class="px-3 py-2">
                                            {{ $instructor['instructor_name'] }}
                                            @if (!empty($instructor['workshops'][0]['volunteer_percentage']))
                                                <span
                                                    class="ml-2 font-normal text-green-700 dark:text-green-400">({{ number_format($instructor['workshops'][0]['volunteer_percentage'], 0) }}%)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    {{-- 2. Filas de talleres del instructor --}}
                                    @php $schedGroupIdx = 0; @endphp
                                    @foreach ($instructor['workshops'] as $workshop)
                                        @php
                                            if (($workshop['schedule_rowspan'] ?? 0) > 0) {
                                                $schedGroupIdx++;
                                            }
                                        @endphp
                                        <tr class="{{ $schedGroupIdx % 2 === 1 ? 'ipr-row-odd' : 'ipr-row-even' }}">
                                            @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                <td class="px-3 py-2 pl-6 text-gray-900 dark:text-white align-middle"
                                                    rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                    {{ $workshop['workshop_name'] }}</td>
                                            @endif
                                            @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs align-middle"
                                                    rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                    {{ $workshop['schedule'] }}
                                                    @foreach ($workshop['schedule_modalities'] ?? [] as $mod)
                                                        <div
                                                            class="text-xs text-indigo-500 dark:text-indigo-400 font-medium mt-0.5">
                                                            {{ $mod }}</div>
                                                    @endforeach
                                                </td>
                                            @endif
                                            <td class="px-3 py-2 text-center text-gray-900 dark:text-white">
                                                <span class="font-medium">{{ $workshop['total_students'] }}</span>
                                                @if (!empty($workshop['class_count']))
                                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                        {{ $workshop['class_count'] }}c</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">S/
                                                {{ number_format($workshop['standard_fee'], 2) }}</td>
                                            @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white align-middle"
                                                    rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                    {{ number_format($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'], 2) }}
                                                </td>
                                            @endif
                                            @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                @php
                                                    $revenue =
                                                        $workshop['schedule_revenue'] ?? $workshop['monthly_revenue'];
                                                    $payout = $workshop['schedule_amount'] ?? $workshop['amount'];
                                                @endphp
                                                <td class="px-3 py-2 text-right font-semibold text-purple-600 dark:text-purple-400 align-middle"
                                                    rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                    {{ number_format($payout, 2) }}</td>
                                                <td class="px-3 py-2 text-right text-blue-600 dark:text-blue-400 align-middle"
                                                    rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                    {{ number_format($revenue - $payout, 2) }}</td>
                                            @endif
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $workshop['payment_status'] === 'Pagado'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                                    {{ $workshop['payment_status'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400">
                                                {{ $workshop['document_number'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    {{-- 3. Subtotal del instructor --}}
                                    @php
                                        $subtotalIngresos = collect($instructor['workshops'])
                                            ->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)
                                            ->sum(fn($w) => $w['schedule_revenue'] ?? $w['monthly_revenue']);
                                        $subtotalFavor = collect($instructor['workshops'])
                                            ->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)
                                            ->sum(function ($w) {
                                                return ($w['schedule_revenue'] ?? $w['monthly_revenue']) -
                                                    ($w['schedule_amount'] ?? $w['amount']);
                                            });
                                    @endphp
                                    <tr
                                        class="bg-purple-50/50 dark:bg-purple-900/10 border-t border-purple-200 dark:border-purple-800">
                                        <td colspan="4"
                                            class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">
                                            Subtotal {{ $instructor['instructor_name'] }}:
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                            S/ {{ number_format($subtotalIngresos, 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold text-purple-700 dark:text-purple-300">
                                            S/ {{ number_format($instructor['subtotal'], 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-blue-600 dark:text-blue-400">
                                            S/ {{ number_format($subtotalFavor, 2) }}
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @php
                                $footerVolIngresos = collect($allInstructorPayments['volunteer'])->sum(function ($i) {
                                    return collect($i['workshops'])->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)->sum(fn($w) => $w['schedule_revenue'] ?? $w['monthly_revenue']);
                                });
                                $footerVolFavor = $footerVolIngresos - $totalAmount['volunteer'];
                            @endphp
                            <tfoot>
                                <tr class="bg-purple-100 dark:bg-purple-900/40 font-bold border-t-2 border-purple-300">
                                    <td colspan="4" class="px-3 py-3 text-right text-gray-900 dark:text-white">TOTAL VOLUNTARIOS:</td>
                                    <td class="px-3 py-3 text-right text-gray-900 dark:text-white">S/ {{ number_format($footerVolIngresos, 2) }}</td>
                                    <td class="px-3 py-3 text-right text-purple-700 dark:text-purple-300">S/ {{ number_format($totalAmount['volunteer'], 2) }}</td>
                                    <td class="px-3 py-3 text-right text-blue-600 dark:text-blue-400">S/ {{ number_format($footerVolFavor, 2) }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Sección: Por Horas -->
            @if (!empty($allInstructorPayments['hourly']))
                <h4 class="text-lg mt-2 font-medium text-gray-900 dark:text-white">
                    Por Horas
                </h4>
                <div class="relative rounded-lg mt-2 border border-gray-200 dark:border-gray-700">
                    <table class="w-full table-auto text-sm">
                        <thead>
                            <tr>
                                <th
                                    class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Taller</th>
                                <th
                                    class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Horario</th>
                                <th
                                    class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Inscritos</th>
                                <th
                                    class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Tarifa Mensual</th>
                                <th
                                    class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Horas</th>
                                <th
                                    class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Tarifa/hora</th>
                                <th
                                    class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Monto a Pagar</th>
                                <th
                                    class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Estado</th>
                                <th
                                    class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    Recibo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            {{-- ============================================================ --}}
                            {{-- SECCIÓN: POR HORAS                                          --}}
                            {{-- Estructura por instructor:                                  --}}
                            {{--   1. Fila agrupadora (nombre del instructor)                --}}
                            {{--   2. Filas de talleres (taller, horario, horas, tarifa/hr)  --}}
                            {{--   3. Fila subtotal del instructor                           --}}
                            {{-- ============================================================ --}}
                            @foreach ($allInstructorPayments['hourly'] as $instructor)
                                {{-- 1. Fila agrupadora del instructor --}}
                                <tr class="ipr-instructor-header">
                                    <td colspan="9" class="px-3 py-2">
                                        {{ $instructor['instructor_name'] }}
                                    </td>
                                </tr>
                                {{-- 2. Filas de talleres del instructor --}}
                                @php $schedGroupIdx = 0; @endphp
                                @foreach ($instructor['workshops'] as $workshop)
                                    @php
                                        if (($workshop['schedule_rowspan'] ?? 0) > 0) {
                                            $schedGroupIdx++;
                                        }
                                    @endphp
                                    <tr class="{{ $schedGroupIdx % 2 === 1 ? 'ipr-row-odd' : 'ipr-row-even' }}">
                                        @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                            <td class="px-3 py-2 pl-6 text-gray-900 dark:text-white align-middle"
                                                rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                {{ $workshop['workshop_name'] }}</td>
                                        @endif
                                        @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs align-middle"
                                                rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                {{ $workshop['schedule'] }}
                                                @foreach ($workshop['schedule_modalities'] ?? [] as $mod)
                                                    <div
                                                        class="text-xs text-indigo-500 dark:text-indigo-400 font-medium mt-0.5">
                                                        {{ $mod }}</div>
                                                @endforeach
                                            </td>
                                        @endif
                                        <td class="px-3 py-2 text-center text-gray-900 dark:text-white">
                                            <span class="font-medium">{{ $workshop['total_students'] }}</span>
                                            @if (!empty($workshop['class_count']))
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                    {{ $workshop['class_count'] }}c</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-300">S/
                                            {{ number_format($workshop['standard_fee'], 2) }}</td>
                                        <td class="px-3 py-2 text-center text-gray-900 dark:text-white">
                                            @if (empty($workshop['is_secondary_tier']))
                                                {{ number_format($workshop['hours_worked'], 1) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td
                                            class="px-3 py-2 text-center text-green-600 dark:text-green-400 font-medium">
                                            S/ {{ number_format($workshop['hourly_rate'], 2) }}</td>
                                        <td
                                            class="px-3 py-2 text-right font-semibold text-green-600 dark:text-green-400">
                                            @if (empty($workshop['is_secondary_tier']))
                                                S/ {{ number_format($workshop['amount'], 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $workshop['payment_status'] === 'Pagado'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                                {{ $workshop['payment_status'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center text-xs text-gray-600 dark:text-gray-400">
                                            {{ $workshop['document_number'] ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- 3. Subtotal del instructor --}}
                                <tr
                                    class="bg-green-50/50 dark:bg-green-900/10 border-t border-green-200 dark:border-green-800">
                                    <td colspan="6"
                                        class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">
                                        Subtotal {{ $instructor['instructor_name'] }}:
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-green-700 dark:text-green-300">
                                        S/ {{ number_format($instructor['subtotal'], 2) }}
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-green-100 dark:bg-green-900/40 font-bold border-t-2 border-green-300">
                                <td colspan="6" class="px-3 py-3 text-right text-gray-900 dark:text-white">TOTAL POR
                                    HORAS:</td>
                                <td class="px-3 py-3 text-right text-green-700 dark:text-green-300">S/
                                    {{ number_format($totalAmount['hourly'], 2) }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        @endif

        @if (empty($allInstructorPayments) && $selectedMonthlyPeriodId)
            <x-filament::section>
                <div class="text-center py-8">
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay registros</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron pagos de profesores en el período seleccionado.
                    </p>
                </div>
            </x-filament::section>
        @endif

        @if (!$selectedMonthlyPeriodId)
            <x-filament::section>
                <div class="text-center py-8">
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un período mensual
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Selecciona un período para ver todos los pagos de profesores registrados.
                    </p>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
