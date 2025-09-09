<div x-data="workshopSelector()"
     data-previous-workshops="{{ json_encode($getViewData()['previous_workshops'] ?? []) }}"
     data-student-id="{{ $getViewData()['student_id'] ?? '' }}">
    @php
        $workshops = $getViewData()['workshops'] ?? collect();
        $studentId = $getViewData()['student_id'] ?? null;
        $student = $studentId ? \App\Models\Student::find($studentId) : null;
        $isMaintenanceCurrent = $student ? $student->isMaintenanceCurrent() : false;
        $previousWorkshopIds = $getViewData()['previous_workshops'] ?? [];

        // OBTENER EL MES ANTERIOR BASADO EN EL PERÍODO SELECCIONADO
        $selectedMonthlyPeriodId = $getViewData()['selected_monthly_period_id'] ?? null;
        $previousMonthName = 'Mes Anterior';

        if ($selectedMonthlyPeriodId) {
            $currentPeriod = \App\Models\MonthlyPeriod::find($selectedMonthlyPeriodId);
            if ($currentPeriod) {
                // Calcular mes anterior
                $previousMonth = $currentPeriod->month - 1;
                $previousYear = $currentPeriod->year;

                if ($previousMonth < 1) {
                    $previousMonth = 12;
                    $previousYear -= 1;
                }

                // Crear fecha del mes anterior para obtener el nombre
                $previousDate = \Carbon\Carbon::create($previousYear, $previousMonth, 1);
                $previousMonthName = ucfirst($previousDate->locale('es')->monthName);
            }
        }

        // CREAR VARIABLES PARA PASAR A JAVASCRIPT
        $workshopsForJs = $workshops->values()->toArray();
        $selectedWorkshopsForJs = $workshops->where('selected', true)->pluck('id')->toArray();
        $previousWorkshopIdsForJs = $previousWorkshopIds;
    @endphp

    <style>
        .workshop-card {
            transition: all 0.2s ease-in-out;
        }
        .workshop-card:hover {
            transform: translateY(-2px);
        }
        input[type="text"]:focus {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }
        [x-cloak] {
            display: none !important;
        }
        .notification-fade {
            animation: fadeInOut 3s forwards;
        }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateX(100%); }
            15% { opacity: 1; transform: translateX(0); }
            85% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(100%); }
        }
    </style>

    <!-- Notificación de cupos agotados -->
    <div x-cloak x-show="showNotification"
         class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 notification-fade">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <span x-text="notificationMessage"></span>
        </div>
    </div>

    @if(!$studentId)
        <div class="text-center py-8 text-gray-500">
            <p class="text-lg">Primero selecciona un estudiante para ver los talleres disponibles</p>
        </div>
    @elseif(!$isMaintenanceCurrent)
        <div class="text-center py-8">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-red-400 mb-2">Mantenimiento Mensual Pendiente</h3>
                <p class="text-red-700 mb-4">
                    El estudiante <strong>{{ $student->first_names }} {{ $student->last_names }}</strong> no está al día con el pago del mantenimiento mensual.
                </p>
                <p class="text-sm text-red-600">
                    Para proceder con la inscripción, primero debe ponerse al día con el mantenimiento mensual.
                    Contacte al área administrativa para regularizar el pago.
                </p>
            </div>
        </div>
    @else
        <!-- Sección de Talleres Previos -->
        <div x-cloak x-show="previousWorkshops.length > 0" class="mb-8">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800">Talleres del Mes Anterior ({{ $previousMonthName }})</h3>
                        <p class="text-sm text-blue-700">
                            <span x-text="previousWorkshops.length"></span> talleres - 
                            <span x-text="selectedPreviousCount"></span> seleccionados para continuar
                        </p>
                    </div>
                </div>

                <!-- Grid de talleres previos -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="workshop in previousWorkshops" x-bind:key="'prev_' + workshop.id">
                        <div 
                            class="workshop-card border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md relative"
                            x-bind:class="{
                                'border-blue-500 bg-blue-100 ring-2 ring-blue-200': selectedWorkshops.includes(workshop.id),
                                'border-blue-300 bg-blue-50 hover:border-blue-400': !selectedWorkshops.includes(workshop.id)
                            }"
                            x-on:click="toggleWorkshop(workshop.id)"
                        >
                            <!-- Badge de taller previo -->
                            <div class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full font-medium z-5">
                                Mes Anterior
                            </div>

                            <!-- Header del taller (igual que el grid principal) -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-blue-900 text-lg" x-text="workshop.name"></h4>
                                    <div class="flex items-center mt-1">
                                        <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="text-sm text-blue-800" x-text="workshop.instructor"></span>
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <div
                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                        x-bind:class="{
                                            'border-primary-500 bg-primary-500': selectedWorkshops.includes(workshop.id),
                                            'border-gray-300': !selectedWorkshops.includes(workshop.id)
                                        }"
                                    >
                                        <svg
                                            class="w-4 h-4 text-white"
                                            x-cloak
                                            x-show="selectedWorkshops.includes(workshop.id)"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del horario -->
                            <div class="space-y-1 mt-3">
                                <div class="flex items-center text-sm text-blue-700">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-text="workshop.day"></span>
                                </div>
                                <div class="flex items-center text-sm text-blue-700">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-text="workshop.start_time + ' - ' + workshop.end_time"></span>
                                </div>
                            </div>

                            <div class="border-t border-blue-200 pt-2 mt-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-blue-700">Precio:</span>
                                    <span class="font-semibold text-blue-900" x-text="'S/ ' + parseFloat(workshop.price).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Header con contador y buscador -->
        <div class="mb-6">
            <div x-cloak x-show="previousWorkshops.length === 0" class="mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div>
                            <h4 class="text-sm font-medium text-green-800">No se registraron talleres en el mes pasado</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-500">
                    <span x-text="selectedCount"></span> talleres seleccionados
                    <span x-cloak x-show="previousWorkshops.length > 0" class="text-blue-600">
                        (<span x-text="previousWorkshops.length"></span> del mes anterior)
                    </span>
                </div>
            </div>

            <!-- Barra de búsqueda -->
            <div class="relative mb-4">
                <input
                    x-model="searchQuery"
                    type="text"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="Buscar talleres por nombre, instructor o día de la semana..."
                />
            </div>

            <!-- Contador de resultados -->
            <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                <span x-text="getResultsText()"></span>
                <div x-cloak x-show="searchQuery.length > 0" class="flex items-center space-x-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                        Búsqueda: "<span x-text="searchQuery"></span>"
                    </span>
                    <button
                        x-on:click="clearSearch()"
                        class="text-blue-600 hover:text-blue-800 text-xs underline"
                        type="button"
                    >
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid de talleres -->
        <div class="min-h-[400px]">
            <!-- Talleres filtrados y paginados -->
            <div x-cloak x-show="paginatedWorkshops.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                    <template x-for="workshop in paginatedWorkshops" x-bind:key="workshop.id">
                        <div
                            class="workshop-card border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md relative"
                            x-bind:class="{
                                'border-primary-500 bg-primary-50 ring-2 ring-primary-200': selectedWorkshops.includes(workshop.id),
                                'border-gray-200 bg-white hover:border-gray-300': !selectedWorkshops.includes(workshop.id) && !workshop.is_full,
                                'border-red-200 bg-red-50 opacity-60 cursor-not-allowed': workshop.is_full
                            }"
                            x-on:click="!workshop.is_full && toggleWorkshop(workshop.id)"
                        >
                            <!-- Badge de cupos agotados -->
                            <div x-cloak x-show="workshop.is_full" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium z-10">
                                Cupos Agotados
                            </div>

                            <!-- Header del taller -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 text-lg" x-text="workshop.name"></h4>
                                    <div class="flex items-center mt-1">
                                        <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-600" x-text="workshop.instructor"></span>
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <div
                                        class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                        x-bind:class="{
                                            'border-primary-500 bg-primary-500': selectedWorkshops.includes(workshop.id),
                                            'border-gray-300': !selectedWorkshops.includes(workshop.id) && !workshop.is_full,
                                            'border-red-300 bg-red-100': workshop.is_full
                                        }"
                                    >
                                        <!-- Checkmark para seleccionados -->
                                        <svg
                                            class="w-4 h-4 text-white"
                                            x-cloak
                                            x-show="selectedWorkshops.includes(workshop.id)"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <!-- X para cupos agotados -->
                                        <svg
                                            class="w-4 h-4 text-red-500"
                                            x-cloak
                                            x-show="workshop.is_full"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del horario -->
                            <div class="space-y-2 mt-2 mb-3">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 font-medium" x-text="workshop.day"></span>
                                </div>
                                <div class="flex pb-4 items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700" x-text="workshop.start_time + ' - ' + workshop.end_time"></span>
                                </div>
                            </div>

                            <!-- Información adicional CON CUPOS -->
                            <div class="border-t pt-3 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Precio:</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="'S/ ' + parseFloat(workshop.price).toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Número de clases:</span>
                                    <span class="text-sm font-medium text-gray-700" x-text="workshop.max_classes + ' clases'"></span>
                                </div>
                                <!-- Información de cupos -->
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Cupos disponibles:</span>
                                    <span
                                        class="text-sm font-medium"
                                        x-bind:class="{
                                            'text-red-600': workshop.available_spots <= 0,
                                            'text-yellow-600': workshop.available_spots <= 3 && workshop.available_spots > 0,
                                            'text-green-600': workshop.available_spots > 3
                                        }"
                                        x-text="workshop.available_spots + '/' + workshop.capacity"
                                    ></span>
                                </div>
                            </div>

                            <!-- Mensaje cuando no hay cupos -->
                            <div x-cloak x-show="workshop.is_full" class="mt-3 p-2 bg-red-100 border border-red-200 rounded text-center">
                                <p class="text-xs text-red-600 font-medium">No hay cupos disponibles</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Estado vacío cuando no hay resultados -->
            <div x-cloak x-show="filteredWorkshops.length === 0 && searchQuery.length > 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron talleres</h3>
                <p class="text-gray-500 mb-4">
                    No hay talleres que coincidan con "<span x-text="searchQuery" class="font-medium"></span>"
                </p>
                <button
                    x-on:click="clearSearch()"
                    class="text-primary-600 hover:text-primary-800 font-medium"
                    type="button"
                >
                    Ver todos los talleres
                </button>
            </div>

            <!-- Estado vacío cuando no hay talleres -->
            <div x-cloak x-show="allWorkshops.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay talleres disponibles</h3>
                <p class="text-gray-500">Contacta al administrador para crear talleres</p>
            </div>

            <!-- Controles de paginación avanzada -->
            <div x-cloak x-show="totalPages > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center text-sm text-gray-500">
                    <span>Mostrando página </span>
                    <span x-text="currentPage" class="font-medium text-gray-900 mx-1"></span>
                    <span> de </span>
                    <span x-text="totalPages" class="font-medium text-gray-900 mx-1"></span>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Botón anterior -->
                    <button
                        x-on:click.stop="previousPage()"
                        x-bind:disabled="currentPage === 1"
                        x-bind:class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition-colors"
                        type="button"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <!-- Números de página -->
                    <template x-for="page in getPageNumbers()" x-bind:key="page">
                        <button
                            x-on:click.stop="goToPage(page)"
                            x-bind:class="currentPage === page ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
                            class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                            type="button"
                            x-text="page"
                        ></button>
                    </template>

                    <!-- Botón siguiente -->
                    <button
                        x-on:click.stop="nextPage()"
                        x-bind:disabled="currentPage === totalPages"
                        x-bind:class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition-colors"
                        type="button"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if($workshops->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-6 h-6 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-lg">No hay talleres disponibles</p>
                <p class="text-sm">Contacta al administrador para crear talleres</p>
            </div>
        @endif

    <input type="hidden"
           name="selected_workshops"
           x-model="selectedWorkshopsJson"
           wire:model="data.selected_workshops" />

    <script>
    function workshopSelector() {
        return {
            allWorkshops: @json($workshopsForJs),
            selectedWorkshops: @json($selectedWorkshopsForJs),
            previousWorkshopIds: [],
            lastStudentId: null,
            searchQuery: '',
            currentPage: 1,
            perPage: 6,
            showNotification: false,
            notificationMessage: '',

            get selectedCount() {
                return this.selectedWorkshops.length;
            },

            get selectedPreviousCount() {
                return this.previousWorkshops.filter(workshop => 
                    this.selectedWorkshops.includes(workshop.id)
                ).length;
            },

            get selectedWorkshopsJson() {
                return JSON.stringify(this.selectedWorkshops);
            },

            get filteredWorkshops() {
                const baseWorkshops = this.allWorkshops.filter(workshop =>
                    !this.previousWorkshopIds.includes(workshop.id)
                );

                if (this.searchQuery.trim() === '') {
                    return baseWorkshops;
                } else {
                    const query = this.searchQuery.toLowerCase().trim();
                    return baseWorkshops.filter(workshop => {
                        return workshop.name.toLowerCase().includes(query) ||
                            workshop.instructor.toLowerCase().includes(query) ||
                            workshop.day.toLowerCase().includes(query);
                    });
                }
            },

            get previousWorkshops() {
                return this.allWorkshops.filter(workshop =>
                    this.previousWorkshopIds.includes(workshop.id)
                );
            },

            get totalPages() {
                return Math.ceil(this.filteredWorkshops.length / this.perPage);
            },

            get paginatedWorkshops() {
                const start = (this.currentPage - 1) * this.perPage;
                const end = start + this.perPage;
                return this.filteredWorkshops.slice(start, end);
            },

            init() {
                this.updatePreviousWorkshops();

                const observer = new MutationObserver(() => {
                    this.updatePreviousWorkshops();
                });

                observer.observe(this.$el, {
                    attributes: true,
                    attributeFilter: ['data-previous-workshops', 'data-student-id']
                });
            },

            updatePreviousWorkshops() {
                const currentStudentId = this.$el.getAttribute('data-student-id');

                if (currentStudentId !== this.lastStudentId) {
                    this.lastStudentId = currentStudentId;

                    const dataAttr = this.$el.getAttribute('data-previous-workshops');
                    if (dataAttr) {
                        try {
                            this.previousWorkshopIds = JSON.parse(dataAttr);
                        } catch (e) {
                            console.error('Error parsing data-previous-workshops:', e);
                            this.previousWorkshopIds = [];
                        }
                    } else {
                        this.previousWorkshopIds = [];
                    }
                }
            },

            clearSearch() {
                this.searchQuery = '';
                this.currentPage = 1;
            },

            getResultsText() {
                const total = this.filteredWorkshops.length;
                const available = this.filteredWorkshops.filter(w => !w.is_full).length;
                const full = total - available;

                if (this.searchQuery.length > 0) {
                    return `${total} talleres encontrados${full > 0 ? ` (${full} sin cupos)` : ''}`;
                }
                return `${total} talleres disponibles${full > 0 ? ` (${full} sin cupos)` : ''}`;
            },

            toggleWorkshop(workshopId) {
                const workshop = this.allWorkshops.find(w => w.id === workshopId);
                if (workshop && workshop.is_full) {
                    this.showCapacityAlert(workshop.name);
                    return;
                }

                const index = this.selectedWorkshops.indexOf(workshopId);
                if (index > -1) {
                    this.selectedWorkshops.splice(index, 1);
                } else {
                    this.selectedWorkshops.push(workshopId);
                }

                this.$nextTick(() => {
                    const namedInput = document.querySelector('input[name="selected_workshops"]');
                    if (namedInput) {
                        namedInput.value = this.selectedWorkshopsJson;
                        namedInput.dispatchEvent(new Event('input', { bubbles: true }));
                        namedInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    if (window.Livewire) {
                        window.Livewire.emit('workshopsUpdated', this.selectedWorkshops);
                    }
                });
            },

            showCapacityAlert(workshopName) {
                this.notificationMessage = `El taller "${workshopName}" no tiene cupos disponibles`;
                this.showNotification = true;

                setTimeout(() => {
                    this.showNotification = false;
                }, 3000);
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                }
            },

            previousPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                }
            },

            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                }
            },

            getPageNumbers() {
                const total = this.totalPages;
                const current = this.currentPage;
                const pages = [];

                if (total <= 7) {
                    for (let i = 1; i <= total; i++) {
                        pages.push(i);
                    }
                } else {
                    if (current <= 4) {
                        pages.push(1, 2, 3, 4, 5, '...', total);
                    } else if (current >= total - 3) {
                        pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total);
                    } else {
                        pages.push(1, '...', current - 1, current, current + 1, '...', total);
                    }
                }

                return pages.filter(page => page !== '...');
            }
        }
    }
    </script>

</div>