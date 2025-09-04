<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione un profesor y luego el taller específico para ver el kardex de inscripciones
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Tabla kardex -->
        @if(!empty($kardexEnrollments) && $instructorData && $workshopData)
        <x-filament::section>
            <x-slot name="heading">
                Kardex - {{ $instructorData->first_names }} {{ $instructorData->last_names }}
            </x-slot>

            <!-- Información del taller -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Taller</p>
                        <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">{{ $workshopData->workshop->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Horario</p>
                        <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                            @php
                                $dayNames = [
                                    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                                    4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                    7 => 'Domingo', 0 => 'Domingo'
                                ];
                                $dayName = $dayNames[$workshopData->day_of_week] ?? 'Día ' . $workshopData->day_of_week;
                                $startTime = \Carbon\Carbon::parse($workshopData->start_time)->format('H:i');
                                $endTime = \Carbon\Carbon::parse($workshopData->end_time)->format('H:i');
                            @endphp
                            {{ $dayName }} {{ $startTime }}-{{ $endTime }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Documento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código Socio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Apellidos y Nombres</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Condición</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Moneda</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Importe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cajero</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($kardexEnrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $enrollment['fecha'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $enrollment['hora'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['numero_documento'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['codigo_socio'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['apellidos_nombres'] }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $enrollment['condicion'] === 'PAMA'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100' }}">
                                    {{ $enrollment['condicion'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $enrollment['moneda'] }}
                            </td>
                            <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($enrollment['importe'], 2) }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $enrollment['cajero'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($kardexEnrollments) && $selectedInstructor && $selectedWorkshop)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay inscripciones</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    No se encontraron inscripciones para este taller específico.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if(!$selectedInstructor)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un profesor</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona un profesor para ver sus talleres disponibles.
                </p>
            </div>
        </x-filament::section>
        @endif

        @if($selectedInstructor && !$selectedWorkshop)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecciona un taller</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Selecciona un taller específico para ver el kardex de inscripciones.
                </p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
