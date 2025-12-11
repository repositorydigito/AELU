<x-filament-panels::page>
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
        @if(!empty($allInstructorPayments))
        <x-filament::section>
            <x-slot name="heading">
                Resumen de Pagos
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Por Horas</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        S/ {{ number_format($totalAmount, 2) }}
                    </p>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Voluntarios</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        S/ {{ number_format(collect($allInstructorPayments)->where('modality', 'Voluntario')->sum('amount'), 2) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
        @endif

        <!-- Tabla de pagos -->
        @if(!empty($allInstructorPayments))
        <x-filament::section>
            <x-slot name="heading">
                Detalle de Pagos por Profesor
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Profesor</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Taller</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Horario</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Tarifa Mensual</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Inscritos</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Modalidad</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Horas</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Tarifa o %</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Monto</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Estado</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">N° Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($allInstructorPayments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-3 text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $payment['instructor_name'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-gray-900 dark:text-white">
                                {{ $payment['workshop_name'] }}
                            </td>
                            <td class="px-3 py-3 text-gray-600 dark:text-gray-400">
                                {{ $payment['schedule'] }}
                            </td>
                            <td class="px-3 py-3 text-center text-gray-900 dark:text-white">
                                S/ {{ number_format($payment['standard_monthly_fee'] ?? 0, 2) }}
                            </td>
                            <td class="px-3 py-3 text-center text-gray-900 dark:text-white">
                                {{ $payment['total_students'] ?? 0 }}
                            </td>
                            <td class="px-3 py-3 ">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $payment['modality'] === 'Por Horas'
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'
                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100' }}">
                                    {{ $payment['modality'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center text-gray-900 dark:text-white">
                                {{ number_format($payment['hours_worked'], 1) }}
                            </td>
                            <td class="px-3 py-3 text-center text-gray-900 dark:text-white">
                                @if($payment['modality'] === 'Por Horas')
                                    S/ {{ number_format($payment['hourly_rate'], 2) }}
                                @elseif($payment['modality'] === 'Voluntario')
                                    <span class="text-purple-600 dark:text-purple-400">{{ number_format($payment['hourly_rate'], 0) }}%</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-semibold">
                                @if($payment['modality'] === 'Por Horas')
                                    <span class="text-green-600 dark:text-green-400">
                                        S/ {{ number_format($payment['amount'], 2) }}
                                    </span>
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">
                                        S/ {{ number_format($payment['amount'], 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $payment['payment_status'] === 'Pagado'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : ($payment['payment_status'] === 'Pendiente'
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
                                            : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100') }}">
                                    {{ $payment['payment_status'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center text-gray-600 dark:text-gray-400">
                                {{ $payment['document_number'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-900 font-bold">
                            <td colspan="8" class="px-3 py-3 text-sm text-right text-gray-900 dark:text-white">
                                TOTAL:
                            </td>
                            <td class="px-3 py-3 text-sm text-right text-gray-900 dark:text-white">
                                S/ {{ number_format($totalAmount + collect($allInstructorPayments)->where('modality', 'Voluntario')->sum('amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($allInstructorPayments) && $selectedMonthlyPeriodId)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay registros</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron pagos de profesores en el período seleccionado.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(!$selectedMonthlyPeriodId)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un período mensual</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona un período para ver todos los pagos de profesores registrados.
                </p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
