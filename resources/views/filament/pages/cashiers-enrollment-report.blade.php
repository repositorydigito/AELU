<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione un cajero y el rango de fechas para ver todos los pagos registrados
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Sección Resumen -->
        @if(!empty($cashierEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Resumen de Pagos
            </x-slot>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                <div class="flex justify-between items-center text-center">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $paymentSummary['total_enrollments'] }} Inscripciones en total
                        </span>
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">
                            {{ $paymentSummary['cash_count'] }} Efectivo
                        </span>
                        <span class="text-xs text-green-600 dark:text-green-400 ml-2">
                            (S/ {{ number_format($paymentSummary['cash_amount'], 2) }})
                        </span>
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                            {{ $paymentSummary['link_count'] }} Link
                        </span>
                        <span class="text-xs text-purple-600 dark:text-purple-400 ml-2">
                            (S/ {{ number_format($paymentSummary['link_amount'], 2) }})
                        </span>
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            Total: S/ {{ number_format($paymentSummary['total_amount'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </x-filament::section>
        @endif

        <!-- Tabla de inscripciones -->
        @if(!empty($cashierEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Pagos Registrados por {{ collect($cashierEnrollments)->first()['cashier_name'] ?? 'Cajero' }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estudiante</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Talleres</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($cashierEnrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['payment_registered_time'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">Inscr: {{ $enrollment['enrollment_date'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['student_name'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $enrollment['student_code'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['workshops_count'] }} taller(es)</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ Str::limit($enrollment['workshops_list'], 50) }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                S/ {{ number_format($enrollment['total_amount'], 2) }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $enrollment['payment_method'] === 'Efectivo'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                                    {{ $enrollment['payment_method'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['payment_status'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $enrollment['batch_code'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($cashierEnrollments) && $selectedCashier && $selectedDateFrom && $selectedDateTo)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay registros de pago</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron pagos registrados por este cajero en el período seleccionado.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(!$selectedCashier)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un cajero</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona un cajero para ver sus registros de pago de inscripciones.
                </p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
