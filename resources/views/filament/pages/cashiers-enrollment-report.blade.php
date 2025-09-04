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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taller</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Instructor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Clases</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código</th>
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
                                <p class="font-medium">{{ $enrollment['workshop_name'] }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $enrollment['instructor_name'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-center text-gray-900 dark:text-white">
                                {{ $enrollment['number_of_classes'] }}
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
