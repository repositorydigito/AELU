<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Vista de selección de talleres --}}
        @if(!$selectedWorkshop)
            <x-filament::card>
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Selecciona un taller para gestionar la asistencia
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Haz clic en cualquier taller para ver y gestionar la asistencia de sus estudiantes.
                    </p>
                </div>

                {{-- Filtros de búsqueda --}}
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Filtro por nombre --}}
                        <div>
                            <label for="searchName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Buscar por nombre
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="searchName"
                                    wire:model.live="searchName"
                                    placeholder="Nombre del taller..."
                                    autocomplete="off"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                >
                            </div>
                        </div>

                        {{-- Filtro por período --}}
                        <div>
                            <label for="selectedPeriod" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Filtrar por período
                            </label>
                            <select
                                id="selectedPeriod"
                                wire:model.live="selectedPeriod"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Todos los períodos</option>
                                @foreach($monthlyPeriods as $periodId => $periodName)
                                    <option value="{{ $periodId }}">{{ $periodName }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botón limpiar filtros --}}
                        <div class="flex items-end">
                            <button
                                wire:click="clearFilters"
                                class="w-full px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors duration-200 flex items-center justify-center"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Limpiar filtros
                            </button>
                        </div>
                    </div>

                    {{-- Contador de resultados --}}
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Mostrando {{ count($filteredWorkshops) }} de {{ count($workshops) }} talleres
                        @if(!empty($searchName) || !empty($selectedPeriod))
                            (filtrados)
                        @endif
                    </div>
                </div>

                @if(empty($filteredWorkshops))
                    <div class="text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            @if(!empty($searchName) || !empty($selectedPeriod))
                                <h3 class="text-lg font-medium mb-2">No se encontraron talleres</h3>
                                <p>No hay talleres que coincidan con los filtros aplicados.</p>
                                <button wire:click="clearFilters" class="mt-3 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-colors duration-200">
                                    Limpiar filtros
                                </button>
                            @else
                                <h3 class="text-lg font-medium mb-2">No hay talleres disponibles</h3>
                                <p>No se encontraron talleres en el sistema.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                        @foreach($filteredWorkshops as $workshop)
                            <div
                                wire:click="selectWorkshop({{ $workshop['id'] }})"
                                class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 cursor-pointer hover:border-primary-500 hover:shadow-md transition-all duration-200 group"
                            >
                                {{-- Contenido del taller --}}
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-lg mb-2">
                                        {{ $workshop['name'] }}
                                    </h3>

                                    {{-- Instructor --}}
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $workshop['instructor_name'] }}
                                    </div>

                                    {{-- Horario --}}
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ is_array($workshop['day_of_week']) ? implode('/', $workshop['day_of_week']) : ($workshop['day_of_week'] ?? 'N/A') }}
                                    </div>

                                    {{-- Hora --}}
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $workshop['start_time'] }} - {{ $workshop['end_time'] }}
                                    </div>

                                    {{-- Modalidad --}}
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ ucfirst($workshop['modality'] ?? 'No especificada') }}
                                    </div>

                                    {{-- Información adicional --}}
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Estudiantes inscritos:</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $workshop['enrolled_students'] }}/{{ $workshop['capacity'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::card>

        {{-- Vista de gestión de asistencia --}}
        @else
            {{-- Header con información del taller seleccionado --}}
            <x-filament::card>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $selectedWorkshopData['name'] ?? 'Taller Seleccionado' }}
                        </h2>
                        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 mt-2 text-sm text-gray-600 dark:text-gray-400 gap-1 md:gap-0">
                            <span>{{ $selectedWorkshopData['instructor_name'] ?? 'Sin instructor' }}</span>
                            <span class="hidden md:inline">•</span>
                            <span>{{ is_array($selectedWorkshopData['day_of_week'] ?? null) ? implode('/', $selectedWorkshopData['day_of_week']) : ($selectedWorkshopData['day_of_week'] ?? 'N/A') }}</span>
                            <span class="hidden md:inline">•</span>
                            <span>{{ $selectedWorkshopData['start_time'] ?? '' }} - {{ $selectedWorkshopData['end_time'] ?? '' }}</span>
                            <span class="hidden md:inline">•</span>
                            <span>{{ ucfirst($selectedWorkshopData['modality'] ?? 'No especificada') }}</span>
                            <span class="hidden md:inline">•</span>
                            <span>{{ $selectedWorkshopData['enrolled_students'] ?? 0 }} estudiantes</span>
                        </div>
                    </div>
                    <div class="flex md:justify-end">
                        <x-filament::button
                            wire:click="backToSelection"
                            color="gray"
                            icon="heroicon-o-arrow-left"
                        >
                            Volver a Talleres
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

            {{-- Tabla de asistencia --}}
            @if(!empty($workshopClasses) && !empty($studentEnrollments))
                {{-- Información de resumen --}}
                {{-- <x-filament::card>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                Información de Inscripciones Específicas
                            </h3>
                        </div>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Nota:</strong> Los checkboxes solo están habilitados para las fechas específicas que cada estudiante seleccionó durante su inscripción.
                            Las fechas marcadas con "−" indican que el estudiante no se inscribió en esa clase específica.
                        </p>
                    </div>
                </x-filament::card> --}}
                <x-filament::card>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4 mb-4">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                Información de Asistencia y Restricciones
                            </h3>
                        </div>
                        <div class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                            <p><strong>• Inscripciones específicas:</strong> Los checkboxes solo están habilitados para las fechas que cada estudiante seleccionó durante su inscripción.</p>
                            <p><strong>• Restricción temporal:</strong> Solo se puede modificar la asistencia hasta 1 día después de la fecha de clase.</p>
                        </div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="overflow-x-auto">
                        <div class="min-w-full">
                            <table class="w-full table-auto border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700">
                                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600 min-w-[60px]">Foto</th>
                                        <th class="sticky left-[60px] z-10 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600 min-w-[200px]">Nombre</th>
                                        <th class="sticky left-[260px] z-10 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600 min-w-[120px]">Código</th>
                                        <th class="sticky left-[380px] z-10 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600 min-w-[80px]">N° Clases</th>
                                        @foreach($workshopClasses as $index => $class)
                                            @php
                                                $canEditDate = $this->canEditAttendanceForDate($class['class_date']);
                                                $classDate = \Carbon\Carbon::parse($class['class_date']);
                                                $headerClass = $canEditDate ? 'text-gray-500 dark:text-gray-300' : 'text-red-500 dark:text-red-400';
                                            @endphp
                                            <th class="px-3 py-2 text-center text-xs font-medium {{ $headerClass }} uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                                <div class="flex flex-col items-center">
                                                    <span>Clase {{ $index + 1 }}</span>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                                                        {{ $classDate->format('d/m') }}
                                                        @if(!$canEditDate)
                                                            <span class="ml-1" title="Fecha expirada para editar">🔒</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comentarios</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($studentEnrollments as $enrollment)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-[60px]">
                                                @if($enrollment['student']['photo'])
                                                    <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                                                        <img src="{{ asset('storage/' . $enrollment['student']['photo']) }}" alt="Foto" class="w-full h-full object-cover">
                                                    </div>
                                                @else
                                                    <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ substr($enrollment['student']['first_names'], 0, 1) }}{{ substr($enrollment['student']['last_names'], 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="sticky left-[60px] z-10 bg-white dark:bg-gray-800 px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-[200px]"><div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $enrollment['student']['last_names'] }}, {{ $enrollment['student']['first_names'] }}</div></td>
                                            <td class="sticky left-[260px] z-10 bg-white dark:bg-gray-800 px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-[120px]"><div class="text-sm text-gray-900 dark:text-gray-100">{{ $enrollment['student']['student_code'] }}</div></td>
                                            <td class="sticky left-[380px] z-10 bg-white dark:bg-gray-800 px-3 py-2 border-r border-gray-200 dark:border-gray-600 min-w-[80px]"><div class="text-sm text-center text-gray-900 dark:text-gray-100">{{ $enrollment['number_of_classes'] }}</div></td>
                                            @foreach($workshopClasses as $class)
                                                @php
                                                    $key = $enrollment['id'] . '_' . $class['id'];
                                                    $isPresent = $attendanceData[$key]['is_present'] ?? false;
                                                    $isEnrolledInClass = $this->isStudentEnrolledInClass($enrollment, $class['id']);
                                                    $canEditDate = $this->canEditAttendanceForDate($class['class_date']);
                                                @endphp
                                                <td class="px-3 py-2 text-center border-r border-gray-200 dark:border-gray-600">
                                                    <div class="flex justify-center items-center h-6">
                                                        @if($isEnrolledInClass && $canEditDate)
                                                            {{-- Checkbox habilitado para clases inscritas y dentro del rango de fecha --}}
                                                            <input
                                                                type="checkbox"
                                                                @if($isPresent) checked @endif
                                                                wire:click="toggleAttendance({{ $enrollment['id'] }}, {{ $class['id'] }})"
                                                                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                            >
                                                        @elseif($isEnrolledInClass && !$canEditDate)
                                                            {{-- Checkbox deshabilitado por restricción de fecha pero mostrar estado actual --}}
                                                            <input
                                                                type="checkbox"
                                                                @if($isPresent) checked @endif
                                                                disabled
                                                                title="No se puede modificar: {{ $this->getRestrictionMessageForDate($class['class_date']) }}"
                                                                class="w-4 h-4 text-red-400 bg-red-100 border-red-300 rounded opacity-50 cursor-not-allowed"
                                                            >
                                                        @else
                                                            {{-- Indicador visual para clases no inscritas --}}
                                                            <div class="w-4 h-4 bg-gray-200 dark:bg-gray-600 rounded border border-gray-300 dark:border-gray-500 flex items-center justify-center" title="No inscrito en esta clase">
                                                                <span class="text-gray-400 dark:text-gray-500 text-xs font-bold">∅</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                            <td class="px-3 py-2">@php $firstClassKey = $enrollment['id'] . '_' . ($workshopClasses[0]['id'] ?? ''); $comments = $attendanceData[$firstClassKey]['comments'] ?? ''; @endphp<textarea wire:model.defer="attendanceData.{{ $firstClassKey }}.comments" placeholder="Comentarios generales..." rows="2" class="w-full min-w-[200px] px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">{{ $comments }}</textarea></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-filament::card>
            @else
                <x-filament::card>
                    <div class="text-center py-12">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <h3 class="text-lg font-medium mb-2">No hay datos disponibles</h3>
                            <p>El taller seleccionado no tiene clases programadas o estudiantes inscritos.</p>
                        </div>
                    </div>
                </x-filament::card>
            @endif
        @endif
    </div>
</x-filament-panels::page>
