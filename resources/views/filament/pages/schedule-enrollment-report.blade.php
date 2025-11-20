<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione el periodo mensual y el horario para ver las inscripciones
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Tabla de inscripciones -->
        @if(!empty($scheduleEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $workshopData->name ?? 'N/A' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        @php
                            // Manejar day_of_week como array
                            $daysOfWeek = $workshopData->day_of_week;
                            if (is_array($daysOfWeek)) {
                                $dayName = implode('/', $daysOfWeek);
                            } else {
                                $dayName = $daysOfWeek ?? 'N/A';
                            }
                            $startTime = $workshopData->start_time ? \Carbon\Carbon::parse($workshopData->start_time)->format('H:i') : 'N/A';
                            $endTime = $workshopData->end_time ?? 'N/A';
                        @endphp
                        {{ $dayName }} | {{ $startTime }}-{{ $endTime }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Profesor: {{ $workshopData->instructor ? ($workshopData->instructor->first_names . ' ' . $workshopData->instructor->last_names) : 'Sin profesor' }} |
                        Modalidad: {{ $workshopData->modality ?? 'N/A' }} |
                        {{ count($scheduleEnrollments) }} inscripciones
                    </p>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Estudiante</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Fecha Inscripción</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Fecha Pago</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nº Clases</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Monto</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Método</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Estado</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Cajero</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Código Ticket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($scheduleEnrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                <div>
                                    <p class="font-medium">{{ $enrollment['student_name'] }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $enrollment['student_code'] }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['enrollment_date'] }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['payment_registered_time'] }}
                            </td>
                            <td class="px-3 py-3 text-sm text-center text-gray-900 dark:text-white">
                                {{ $enrollment['number_of_classes'] }}
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
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['payment_status'] }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['user_name'] }}
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $enrollment['ticket_code'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($scheduleEnrollments) && $selectedPeriod && $selectedWorkshop)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay registros</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron tickets para el taller seleccionado en este periodo.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(!$selectedPeriod || !$selectedWorkshop)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un periodo y taller</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona el periodo mensual y el taller para ver los tickets.
                </p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
