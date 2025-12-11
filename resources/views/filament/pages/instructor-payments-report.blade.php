<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione un profesor, un período mensual, o ambos para generar el reporte
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Resumen y título dinámico -->
        @if(!empty($instructorPayments))
        <x-filament::section>
            <x-slot name="heading">
                @if($instructorData && $periodData)
                    Pagos de {{ $instructorData->first_names }} {{ $instructorData->last_names }} - {{ $this->generatePeriodName($periodData->month, $periodData->year) }}
                @elseif($instructorData)
                    Historial de Pagos - {{ $instructorData->first_names }} {{ $instructorData->last_names }}
                @elseif($periodData)
                    Pagos del Período - {{ $this->generatePeriodName($periodData->month, $periodData->year) }}
                @else
                    Historial de Pagos
                @endif
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            @if(!$selectedInstructor)
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Instructor</th>
                            @endif
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taller</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Horario</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarifa Mensual</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inscritos</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Horas</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarifa o %</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha de Pago</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($instructorPayments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @if(!$selectedInstructor)
                            <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $payment['instructor_name'] }}</td>
                            @endif
                            <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $payment['workshop_name'] }}</td>
                            <td class="px-4 py-4 text-gray-500 dark:text-gray-400">{{ $payment['workshop_schedule'] }}</td>
                            <td class="px-4 py-4 text-center text-gray-900 dark:text-white">S/ {{ number_format($payment['standard_monthly_fee'], 2) }}</td>
                            <td class="px-4 py-4 text-center text-gray-900 dark:text-white">{{ $payment['total_students'] }}</td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $payment['period_name'] }}</td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $payment['payment_type'] === 'Voluntario'
                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                                    {{ $payment['payment_type'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-gray-900 dark:text-white">
                                {{ number_format($payment['total_hours'], 1) }}
                            </td>
                            <td class="px-4 py-4 text-center font-medium text-gray-900 dark:text-white">
                                @if($payment['payment_type'] === 'Por Horas')
                                    S/ {{ number_format($payment['rate_or_percentage_value'], 2) }}
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">{{ number_format($payment['rate_or_percentage_value'], 0) }}%</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right font-semibold">
                                @if($payment['payment_type'] === 'Por Horas')
                                    <span class="text-green-600 dark:text-green-400">
                                        S/ {{ number_format($payment['calculated_amount'], 2) }}
                                    </span>
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">
                                        S/ {{ number_format($payment['calculated_amount'], 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $payment['payment_date'] }}</td>
                            <td class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">{{ $payment['document_number'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-900 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="{{ $selectedInstructor ? '8' : '9' }}" class="px-4 py-4 text-right text-gray-900 dark:text-white">
                                TOTAL:
                            </td>
                            <td class="px-4 py-4 text-right text-gray-900 dark:text-white">
                                S/ {{ number_format(collect($instructorPayments)->sum('calculated_amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($instructorPayments) && ($selectedInstructor || $selectedPeriod))
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay pagos registrados</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if($instructorData && $periodData)
                        Este profesor no tiene pagos registrados en el período seleccionado.
                    @elseif($instructorData)
                        Este profesor no tiene pagos registrados.
                    @elseif($periodData)
                        No hay pagos registrados en este período.
                    @endif
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(empty($instructorPayments) && !$selectedInstructor && !$selectedPeriod)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Seleccione los filtros</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Seleccione al menos un profesor o un período para ver los pagos.</p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
