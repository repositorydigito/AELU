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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Voluntarios</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        S/ {{ number_format($totalAmount['volunteer'], 2) }}
                    </p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Por Horas</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        S/ {{ number_format($totalAmount['hourly'], 2) }}
                    </p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total General</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        S/ {{ number_format($totalAmount['grand_total'], 2) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <!-- Sección: Voluntarios -->
        @if(!empty($allInstructorPayments['volunteer']))
        <x-filament::section>
            <x-slot name="heading">
                Voluntarios
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Taller</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Horario</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Inscritos</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Ingresos del Taller</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">%</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Monto a Pagar</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($allInstructorPayments['volunteer'] as $instructor)
                            {{-- Fila agrupadora del instructor --}}
                            <tr class="bg-purple-50 dark:bg-purple-900/20">
                                <td colspan="7" class="px-3 py-2 font-semibold text-purple-800 dark:text-purple-300">
                                    {{ $instructor['instructor_name'] }}
                                </td>
                            </tr>
                            {{-- Filas de talleres --}}
                            @foreach($instructor['workshops'] as $workshop)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-3 py-2 pl-6 text-gray-900 dark:text-white">{{ $workshop['workshop_name'] }}</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs">{{ $workshop['schedule'] }}</td>
                                <td class="px-3 py-2 text-center text-gray-900 dark:text-white">{{ $workshop['total_students'] }}</td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white">S/ {{ number_format($workshop['monthly_revenue'], 2) }}</td>
                                <td class="px-3 py-2 text-center text-purple-600 dark:text-purple-400 font-medium">{{ number_format($workshop['volunteer_percentage'], 0) }}%</td>
                                <td class="px-3 py-2 text-right font-semibold text-purple-600 dark:text-purple-400">S/ {{ number_format($workshop['amount'], 2) }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $workshop['payment_status'] === 'Pagado'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                        {{ $workshop['payment_status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                            {{-- Subtotal del instructor --}}
                            <tr class="bg-purple-50/50 dark:bg-purple-900/10 border-t border-purple-200 dark:border-purple-800">
                                <td colspan="5" class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">
                                    Subtotal {{ $instructor['instructor_name'] }}:
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-purple-700 dark:text-purple-300">
                                    S/ {{ number_format($instructor['subtotal'], 2) }}
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-purple-100 dark:bg-purple-900/40 font-bold border-t-2 border-purple-300">
                            <td colspan="5" class="px-3 py-3 text-right text-gray-900 dark:text-white">TOTAL VOLUNTARIOS:</td>
                            <td class="px-3 py-3 text-right text-purple-700 dark:text-purple-300">S/ {{ number_format($totalAmount['volunteer'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
        @endif

        <!-- Sección: Por Horas -->
        @if(!empty($allInstructorPayments['hourly']))
        <x-filament::section>
            <x-slot name="heading">
                Por Horas
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Taller</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Horario</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Horas</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Tarifa/hora</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Monto a Pagar</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($allInstructorPayments['hourly'] as $instructor)
                            {{-- Fila agrupadora del instructor --}}
                            <tr class="bg-green-50 dark:bg-green-900/20">
                                <td colspan="6" class="px-3 py-2 font-semibold text-green-800 dark:text-green-300">
                                    {{ $instructor['instructor_name'] }}
                                </td>
                            </tr>
                            {{-- Filas de talleres --}}
                            @foreach($instructor['workshops'] as $workshop)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-3 py-2 pl-6 text-gray-900 dark:text-white">{{ $workshop['workshop_name'] }}</td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs">{{ $workshop['schedule'] }}</td>
                                <td class="px-3 py-2 text-center text-gray-900 dark:text-white">{{ number_format($workshop['hours_worked'], 1) }}</td>
                                <td class="px-3 py-2 text-center text-green-600 dark:text-green-400 font-medium">S/ {{ number_format($workshop['hourly_rate'], 2) }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-green-600 dark:text-green-400">S/ {{ number_format($workshop['amount'], 2) }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $workshop['payment_status'] === 'Pagado'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                        {{ $workshop['payment_status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                            {{-- Subtotal del instructor --}}
                            <tr class="bg-green-50/50 dark:bg-green-900/10 border-t border-green-200 dark:border-green-800">
                                <td colspan="4" class="px-3 py-2 pl-6 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">
                                    Subtotal {{ $instructor['instructor_name'] }}:
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-green-700 dark:text-green-300">
                                    S/ {{ number_format($instructor['subtotal'], 2) }}
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-green-100 dark:bg-green-900/40 font-bold border-t-2 border-green-300">
                            <td colspan="4" class="px-3 py-3 text-right text-gray-900 dark:text-white">TOTAL POR HORAS:</td>
                            <td class="px-3 py-3 text-right text-green-700 dark:text-green-300">S/ {{ number_format($totalAmount['hourly'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
        @endif
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
