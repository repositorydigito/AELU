<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Seleccionar Profesor
            </x-slot>
            <x-slot name="description">
                Busque y seleccione un profesor para ver su historial de pagos
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Tabla de pagos -->
        @if(!empty($instructorPayments))
        <x-filament::section>
            <x-slot name="heading">
                Historial de Pagos - {{ $instructorData->first_names }} {{ $instructorData->last_names }}
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taller</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Horario</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha de Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Documento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($instructorPayments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $payment['workshop_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payment['workshop_schedule'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $payment['period_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $payment['payment_type'] === 'Voluntario'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' }}">
                                    {{ $payment['payment_type'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">S/ {{ number_format($payment['calculated_amount'], 2) }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $payment['payment_date'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payment['document_number'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($instructorPayments) && $selectedInstructor)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay pagos registrados</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este profesor no tiene pagos registrados.</p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
