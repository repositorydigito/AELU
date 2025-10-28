<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione el rango de fechas para ver todas las inscripciones registradas
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Sección Resumen General -->
        @if(!empty($usersEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Resumen General
            </x-slot>

            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Usuarios</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $overallSummary['total_users'] }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Total Inscripciones</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $overallSummary['total_enrollments'] }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Efectivo</p>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                            {{ $overallSummary['cash_count'] }}
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-400">
                            S/ {{ number_format($overallSummary['cash_amount'], 2) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Link</p>
                        <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                            {{ $overallSummary['link_count'] }}
                        </p>
                        <p class="text-xs text-purple-600 dark:text-purple-400">
                            S/ {{ number_format($overallSummary['link_amount'], 2) }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-blue-200 dark:border-gray-600 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Monto Total</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                        S/ {{ number_format($overallSummary['total_amount'], 2) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <!-- Sección por Usuario -->
        @foreach($usersEnrollments as $userData)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $userData['user_name'] }}
                        </h3>
                    </div>
                    <div class="flex gap-4 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $userData['summary']['total_count'] }} inscripciones
                        </span>
                        <span class="text-green-600 dark:text-green-400">
                            {{ $userData['summary']['cash_count'] }} Efectivo (S/ {{ number_format($userData['summary']['cash_amount'], 2) }})
                        </span>
                        <span class="text-purple-600 dark:text-purple-400">
                            {{ $userData['summary']['link_count'] }} Link (S/ {{ number_format($userData['summary']['link_amount'], 2) }})
                        </span>
                        <span class="font-bold text-gray-900 dark:text-white">
                            Total: S/ {{ number_format($userData['summary']['total_amount'], 2) }}
                        </span>
                    </div>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Fecha Pago</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Estudiante</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Talleres</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Monto</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Método</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Estado</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nº Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($userData['enrollments'] as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['payment_registered_time'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">Inscr: {{ $enrollment['enrollment_date'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['student_name'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $enrollment['student_code'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['workshops_count'] }} taller(es)</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ Str::limit($enrollment['workshops_list'], 40) }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                S/ {{ number_format($enrollment['total_amount'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $enrollment['payment_method'] === 'Efectivo'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' }}">
                                    {{ $enrollment['payment_method'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-900 dark:text-white">
                                {{ $enrollment['payment_status'] }}
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $enrollment['batch_code'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endforeach
        @endif

        @if(empty($usersEnrollments) && $selectedDateFrom && $selectedDateTo)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay registros</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron pagos registrados en el período seleccionado.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(!$selectedDateFrom || !$selectedDateTo)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un rango de fechas</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona las fechas para ver los registros de inscripciones por usuario.
                </p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
