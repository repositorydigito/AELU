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
            background-color: #bbf7d0;
            position: sticky;
            top: 37px;
            z-index: 8;
        }

        .ipr-sidebar-expanded .ipr-instructor-header td {
            top: 30px;
        }

        .ipr-instructor-header td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ipr-sidebar-expanded {
            --ipr-taller-top: 92px;
        }

        .ipr-name-separator td {
            border-top: 2px solid #d1d5db;
        }

        .ipr-summary-amount {
            font-size: 1rem;
        }

        .fi-tabs-item.fi-active {
            color: #166534 !important;
        }

        .fi-tabs-item.fi-active .fi-badge {
            background-color: #bbf7d0 !important;
            color: #14532d !important;
        }
    </style>
    <div class="space-y-6" id="ipr-root" style="--ipr-taller-top: 74px;"
        x-data="{
            init() {
                const root = document.getElementById('ipr-root');
                const sidebar = document.querySelector('aside.fi-sidebar');
                if (!sidebar || !root) return;
                const update = () => {
                    const w = parseInt(window.getComputedStyle(sidebar).width) || 0;
                    root.classList.toggle('ipr-sidebar-expanded', w > 150);
                };
                new ResizeObserver(update).observe(sidebar);
                update();
            }
        }">

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
                Resumen del Mes{{ $periodName ? ' ' . $periodName : '' }}
            </x-slot>
            <div class="overflow-x-auto max-w-4xl mx-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-500">
                            <th class="py-2 pr-6 text-left font-medium text-gray-500"></th>
                            <th class="py-2 px-4 text-right font-semibold text-gray-700 border-l border-gray-300">Ingresos de Taller</th>
                            <th class="py-2 px-4 text-right font-semibold text-gray-700 border-l border-gray-300">Total a Pagar</th>
                            <th class="py-2 px-4 text-right font-semibold text-gray-700 border-l border-gray-300">Pagado</th>
                            <th class="py-2 px-4 text-right font-semibold text-gray-700 border-l border-gray-300">Pendiente</th>
                            <th class="py-2 px-4 text-right font-semibold text-gray-700 border-l border-gray-300">Saldo a Favor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-6 font-medium text-gray-700">Voluntarios</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['volunteer_revenue'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right font-semibold text-green-700 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['volunteer'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['paid_volunteer'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['pending_volunteer'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right ipr-summary-amount border-l border-gray-300 {{ ($totalAmount['volunteer_favor'] ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600' }}">S/ {{ number_format($totalAmount['volunteer_favor'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-6 font-medium text-gray-700">Por Horas</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['hourly_revenue'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right font-semibold text-green-700 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['hourly'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['paid_hourly'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['pending_hourly'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right ipr-summary-amount border-l border-gray-300 {{ ($totalAmount['hourly_favor'] ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600' }}">S/ {{ number_format($totalAmount['hourly_favor'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-400 font-bold">
                            <td class="py-2 pr-6 text-gray-900">Total</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['total_revenue'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-green-700 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['grand_total'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['paid_total'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right text-gray-900 ipr-summary-amount border-l border-gray-300">S/ {{ number_format($totalAmount['pending_total'] ?? 0, 2) }}</td>
                            <td class="py-2 px-4 text-right ipr-summary-amount border-l border-gray-300 {{ ($totalAmount['total_favor'] ?? 0) >= 0 ? 'text-blue-700' : 'text-red-600' }}">S/ {{ number_format($totalAmount['total_favor'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        @if (!empty($allInstructorPayments))
            @php
                $hasVolunteer = !empty($allInstructorPayments['volunteer']);
                $hasHourly    = !empty($allInstructorPayments['hourly']);
                $defaultTab   = $hasVolunteer ? 'volunteer' : 'hourly';
            @endphp

            <div x-data="{ tab: '{{ $defaultTab }}' }">

                <x-filament::tabs>
                    @if ($hasVolunteer)
                        <x-filament::tabs.item
                            alpine-active="tab === 'volunteer'"
                            x-on:click="tab = 'volunteer'">
                            Voluntarios
                            <x-slot name="badge">{{ count($allInstructorPayments['volunteer']) }}</x-slot>
                        </x-filament::tabs.item>
                    @endif
                    @if ($hasHourly)
                        <x-filament::tabs.item
                            alpine-active="tab === 'hourly'"
                            x-on:click="tab = 'hourly'">
                            Por Horas
                            <x-slot name="badge">{{ count($allInstructorPayments['hourly']) }}</x-slot>
                        </x-filament::tabs.item>
                    @endif
                </x-filament::tabs>

                <!-- Tab: Voluntarios -->
                @if ($hasVolunteer)
                    <div x-show="tab === 'volunteer'" x-cloak class="mt-4">
                        <div class="relative rounded-lg border border-gray-200">
                            <div style="max-height:75vh; overflow-y:auto; overflow-x:auto;">
                                <table class="w-full table-auto text-sm">
                                    <thead class="sticky top-0 z-10">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 sticky top-0 z-10 bg-gray-50" style="width:15%">
                                                Taller</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Horario</th>
                                            <th class="px-3 py-2 text-center font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Inscritos</th>
                                            <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Tarifa</th>
                                            <th class="px-3 py-2 text-center font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                %</th>
                                            <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Ingresos</th>
                                            <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Por Pagar</th>
                                            <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                                Saldo a Favor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($allInstructorPayments['volunteer'] as $instructor)
                                            <tr class="ipr-instructor-header">
                                                <td colspan="8" class="px-3 py-2">
                                                    <div style="display:flex; align-items:center; justify-content:space-between;">
                                                        <span>{{ $instructor['instructor_name'] }}</span>
                                                        @if ($instructor['has_receipt'])
                                                            <span style="font-size:0.8rem; font-weight:400; color:#166534;">
                                                                N° {{ $instructor['receipt_document'] }} &nbsp;•&nbsp; {{ $instructor['receipt_date'] }}
                                                            </span>
                                                        @else
                                                            <x-filament::button
                                                                size="xs"
                                                                color="success"
                                                                wire:click="mountAction('registerReceipt', {{ \Illuminate\Support\Js::from(['instructor_id' => $instructor['instructor_id'], 'payment_type' => 'volunteer']) }})"
                                                            >
                                                                Registrar Recibo
                                                            </x-filament::button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @php $schedGroupIdx = 0; @endphp
                                            @foreach ($instructor['workshops'] as $workshop)
                                                @php
                                                    if (($workshop['schedule_rowspan'] ?? 0) > 0) {
                                                        $schedGroupIdx++;
                                                    }
                                                @endphp
                                                <tr class="{{ $schedGroupIdx % 2 === 1 ? 'ipr-row-odd' : 'ipr-row-even' }} {{ ($workshop['name_rowspan'] ?? 0) > 0 ? 'ipr-name-separator' : '' }}">
                                                    @if (($workshop['name_rowspan'] ?? 1) > 0)
                                                        <td class="px-3 py-2 pl-6 text-gray-900"
                                                            rowspan="{{ $workshop['name_rowspan'] }}"
                                                            style="position:sticky; top:var(--ipr-taller-top, 74px); z-index:7; background:white; vertical-align:top; border-top:2px solid #d1d5db;">
                                                            {{ $workshop['workshop_name'] }}</td>
                                                    @endif
                                                    @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                        <td class="px-3 py-2 text-gray-500 text-xs align-middle"
                                                            rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                            {{ $workshop['schedule'] }}
                                                            @foreach ($workshop['schedule_modalities'] ?? [] as $mod)
                                                                <div class="text-xs text-indigo-500 font-medium mt-0.5">
                                                                    {{ $mod }}</div>
                                                            @endforeach
                                                        </td>
                                                    @endif
                                                    <td class="px-3 py-2 text-center text-gray-900">
                                                        <span class="font-medium">{{ $workshop['total_students'] }}</span>
                                                        @if (!empty($workshop['class_count']))
                                                            <div class="text-xs text-gray-400 mt-0.5">
                                                                {{ $workshop['class_count'] }}c</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right text-gray-600">S/
                                                        {{ number_format($workshop['standard_fee'], 2) }}</td>
                                                    <td class="px-3 py-2 text-center text-gray-500 text-xs align-middle">
                                                        {{ number_format($workshop['volunteer_percentage'] ?? 0, 0) }}%
                                                    </td>
                                                    @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                        <td class="px-3 py-2 text-right text-gray-900 align-middle"
                                                            rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                            {{ number_format($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'], 2) }}
                                                        </td>
                                                    @endif
                                                    @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                        @php
                                                            $revenue = $workshop['schedule_revenue'] ?? $workshop['monthly_revenue'];
                                                            $payout  = $workshop['schedule_amount'] ?? $workshop['amount'];
                                                        @endphp
                                                        <td class="px-3 py-2 text-right font-semibold text-purple-600 align-middle"
                                                            rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                            {{ number_format($payout, 2) }}</td>
                                                        <td class="px-3 py-2 text-right text-blue-600 align-middle"
                                                            rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                            {{ number_format($revenue - $payout, 2) }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
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
                                            <tr class="bg-purple-50/50 border-t border-purple-200">
                                                <td colspan="5" class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600">
                                                    Subtotal {{ $instructor['instructor_name'] }}:
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-700">
                                                    S/ {{ number_format($subtotalIngresos, 2) }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-bold text-purple-700">
                                                    S/ {{ number_format($instructor['subtotal'], 2) }}
                                                </td>
                                                <td class="px-3 py-2 text-right text-blue-600">
                                                    S/ {{ number_format($subtotalFavor, 2) }}
                                                </td>
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
                                        <tr class="bg-purple-100 font-bold border-t-2 border-purple-300">
                                            <td colspan="5" class="px-3 py-3 text-right text-gray-900">TOTAL VOLUNTARIOS:</td>
                                            <td class="px-3 py-3 text-right text-gray-900">S/ {{ number_format($footerVolIngresos, 2) }}</td>
                                            <td class="px-3 py-3 text-right text-purple-700">S/ {{ number_format($totalAmount['volunteer'], 2) }}</td>
                                            <td class="px-3 py-3 text-right text-blue-600">S/ {{ number_format($footerVolFavor, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Tab: Por Horas -->
                @if ($hasHourly)
                    <div x-show="tab === 'hourly'" x-cloak class="mt-4">
                        <div class="relative rounded-lg border border-gray-200">
                            <div style="max-height:75vh; overflow-y:auto; overflow-x:auto;">
                            <table class="w-full table-auto text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 sticky top-0 z-10 bg-gray-50" style="width:20%">
                                            Taller</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Horario</th>
                                        <th class="px-3 py-2 text-center font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Inscritos</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Tarifa</th>
                                        <th class="px-3 py-2 text-center font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Honorarios/horas</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Ingresos</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Por Pagar</th>
                                        <th class="px-3 py-2 text-right font-medium text-gray-500 sticky top-0 z-10 bg-gray-50">
                                            Saldo a Favor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($allInstructorPayments['hourly'] as $instructor)
                                        <tr class="ipr-instructor-header">
                                            <td colspan="8" class="px-3 py-2">
                                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                                    <span>{{ $instructor['instructor_name'] }}</span>
                                                    @if ($instructor['has_receipt'])
                                                        <span style="font-size:0.8rem; font-weight:400; color:#166534;">
                                                            N° {{ $instructor['receipt_document'] }} &nbsp;•&nbsp; {{ $instructor['receipt_date'] }}
                                                        </span>
                                                    @else
                                                        <x-filament::button
                                                            size="xs"
                                                            color="success"
                                                            wire:click="mountAction('registerReceipt', {{ \Illuminate\Support\Js::from(['instructor_id' => $instructor['instructor_id'], 'payment_type' => 'hourly']) }})"
                                                        >
                                                            Registrar Recibo
                                                        </x-filament::button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @php $schedGroupIdx = 0; @endphp
                                        @foreach ($instructor['workshops'] as $workshop)
                                            @php
                                                if (($workshop['schedule_rowspan'] ?? 0) > 0) {
                                                    $schedGroupIdx++;
                                                }
                                            @endphp
                                            <tr class="{{ $schedGroupIdx % 2 === 1 ? 'ipr-row-odd' : 'ipr-row-even' }} {{ ($workshop['name_rowspan'] ?? 0) > 0 ? 'ipr-name-separator' : '' }}">
                                                @if (($workshop['name_rowspan'] ?? 1) > 0)
                                                    <td class="px-3 py-2 pl-6 text-gray-900"
                                                        rowspan="{{ $workshop['name_rowspan'] }}"
                                                        style="position:sticky; top:var(--ipr-taller-top, 74px); z-index:7; background:white; vertical-align:top; border-top:2px solid #d1d5db;">
                                                        {{ $workshop['workshop_name'] }}</td>
                                                @endif
                                                @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                    <td class="px-3 py-2 text-gray-500 text-xs align-middle"
                                                        rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                        {{ $workshop['schedule'] }}
                                                        @foreach ($workshop['schedule_modalities'] ?? [] as $mod)
                                                            <div class="text-xs text-indigo-500 font-medium mt-0.5">
                                                                {{ $mod }}</div>
                                                        @endforeach
                                                    </td>
                                                @endif
                                                <td class="px-3 py-2 text-center text-gray-900">
                                                    <span class="font-medium">{{ $workshop['total_students'] }}</span>
                                                    @if (!empty($workshop['class_count']))
                                                        <div class="text-xs text-gray-400 mt-0.5">
                                                            {{ $workshop['class_count'] }}c</div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-600">S/
                                                    {{ number_format($workshop['standard_fee'], 2) }}</td>
                                                @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                    <td class="px-3 py-2 text-center text-green-600 font-medium align-middle"
                                                        rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                        S/ {{ number_format($workshop['hourly_rate'], 2) }}
                                                        <div class="text-xs text-gray-400 mt-0.5">
                                                            {{ number_format($workshop['hours_worked'], 1) }} hrs
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2 text-right text-gray-900 align-middle"
                                                        rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                        {{ number_format($workshop['schedule_revenue'] ?? $workshop['monthly_revenue'], 2) }}
                                                    </td>
                                                @endif
                                                @if (($workshop['schedule_rowspan'] ?? 1) > 0)
                                                    <td class="px-3 py-2 text-right font-semibold text-green-600 align-middle"
                                                        rowspan="{{ $workshop['schedule_rowspan'] }}">
                                                        S/ {{ number_format($workshop['schedule_amount'] ?? $workshop['amount'], 2) }}
                                                    </td>
                                                    @php
                                                        $hrRevenue = $workshop['schedule_revenue'] ?? $workshop['monthly_revenue'];
                                                        $hrAmount  = $workshop['schedule_amount']  ?? $workshop['amount'];
                                                    @endphp
                                                    <td class="px-3 py-2 text-right text-blue-600 align-middle"
                                                        rowspan="{{ $workshop['schedule_rowspan'] }}">S/
                                                        {{ number_format($hrRevenue - $hrAmount, 2) }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @php
                                            $subtotalHrIngresos = collect($instructor['workshops'])
                                                ->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)
                                                ->sum(fn($w) => $w['schedule_revenue'] ?? $w['monthly_revenue']);
                                            $subtotalHrFavor = collect($instructor['workshops'])
                                                ->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)
                                                ->sum(function ($w) {
                                                    return ($w['schedule_revenue'] ?? $w['monthly_revenue']) -
                                                           ($w['schedule_amount']  ?? $w['amount']);
                                                });
                                        @endphp
                                        <tr class="bg-green-50/50 border-t border-green-200">
                                            <td colspan="5" class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600">
                                                Subtotal {{ $instructor['instructor_name'] }}:
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700">
                                                S/ {{ number_format($subtotalHrIngresos, 2) }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-bold text-green-700">
                                                S/ {{ number_format($instructor['subtotal'], 2) }}
                                            </td>
                                            <td class="px-3 py-2 text-right text-blue-600">
                                                S/ {{ number_format($subtotalHrFavor, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $footerHrIngresos = collect($allInstructorPayments['hourly'])->sum(function ($i) {
                                        return collect($i['workshops'])
                                            ->filter(fn($w) => ($w['schedule_rowspan'] ?? 1) > 0)
                                            ->sum(fn($w) => $w['schedule_revenue'] ?? $w['monthly_revenue']);
                                    });
                                    $footerHrFavor = $footerHrIngresos - $totalAmount['hourly'];
                                @endphp
                                <tfoot>
                                    <tr class="bg-green-100 font-bold border-t-2 border-green-300">
                                        <td colspan="5" class="px-3 py-3 text-right text-gray-900">TOTAL POR HORAS:</td>
                                        <td class="px-3 py-3 text-right text-gray-900">S/ {{ number_format($footerHrIngresos, 2) }}</td>
                                        <td class="px-3 py-3 text-right text-green-700">S/ {{ number_format($totalAmount['hourly'], 2) }}</td>
                                        <td class="px-3 py-3 text-right text-blue-600">S/ {{ number_format($footerHrFavor, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        @if (empty($allInstructorPayments) && $selectedMonthlyPeriodId)
            <x-filament::section>
                <div class="text-center py-8">
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No hay registros</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        No se encontraron pagos de profesores en el período seleccionado.
                    </p>
                </div>
            </x-filament::section>
        @endif

        @if (!$selectedMonthlyPeriodId)
            <x-filament::section>
                <div class="text-center py-8">
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Selecciona un período mensual
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Selecciona un período para ver todos los pagos de profesores registrados.
                    </p>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
