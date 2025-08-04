@php
    $workshops = $getViewData()['workshops'] ?? collect();
    $studentId = $getViewData()['student_id'] ?? null;
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    $isMaintenancePaid = $student ? $student->monthly_maintenance_paid : false;
    $perPage = 6;
    $totalWorkshops = $workshops->count();
    $totalPages = ceil($totalWorkshops / $perPage);
@endphp

<div x-data="workshopSelector()" class="space-y-4">
    @if(!$studentId)
        <div class="text-center py-8 text-gray-500">
            <p class="text-lg">Primero selecciona un estudiante para ver los talleres disponibles</p>
        </div>
    @elseif(!$isMaintenancePaid)
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
        <!-- Header con contador y buscador -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Talleres Disponibles</h3>
                <div class="text-sm text-gray-500">
                    <span x-text="selectedCount"></span> talleres seleccionados
                </div>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="relative mb-4">
                {{-- <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div> --}}
                <input
                    x-model="searchQuery"
                    @input="updateSearch()"
                    type="text"
                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="Buscar talleres por nombre, instructor o día de la semana..."
                />
                <!-- Botón limpiar búsqueda -->
                <div x-show="searchQuery.length > 0" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button
                        @click="clearSearch()"
                        type="button"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Contador de resultados -->
            <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                <span x-text="getResultsText()"></span>
                <div x-show="searchQuery.length > 0" class="flex items-center space-x-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                        Búsqueda: "<span x-text="searchQuery"></span>"
                    </span>
                    <button 
                        @click="clearSearch()"
                        class="text-blue-600 hover:text-blue-800 text-xs underline"
                        type="button"
                    >
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid de talleres con búsqueda -->
        <div class="min-h-[400px]">
            <!-- Talleres filtrados y paginados -->
            <div x-show="paginatedWorkshops.length > 0" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                <template x-for="(workshop, index) in paginatedWorkshops" :key="workshop.id">
                    <div 
                        class="workshop-card border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md"
                        :class="selectedWorkshops.includes(workshop.id) ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200' : 'border-gray-200 bg-white hover:border-gray-300'"
                        @click="toggleWorkshop(workshop.id)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                    >
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
                                    :class="selectedWorkshops.includes(workshop.id) ? 'border-primary-500 bg-primary-500' : 'border-gray-300'"
                                >
                                    <svg 
                                        class="w-4 h-4 text-white" 
                                        :class="selectedWorkshops.includes(workshop.id) ? 'block' : 'hidden'"
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
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

                        <!-- Información adicional -->
                        <div class="border-t pt-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Precio:</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="'S/ ' + parseFloat(workshop.price).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Número de clases:</span>
                                <span class="text-sm font-medium text-gray-700" x-text="workshop.max_classes + ' clases'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Estado vacío cuando no hay resultados -->
            <div x-show="filteredWorkshops.length === 0 && searchQuery.length > 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron talleres</h3>
                <p class="text-gray-500 mb-4">
                    No hay talleres que coincidan con "<span x-text="searchQuery" class="font-medium"></span>"
                </p>
                <button 
                    @click="clearSearch()"
                    class="text-primary-600 hover:text-primary-800 font-medium"
                    type="button"
                >
                    Ver todos los talleres
                </button>
            </div>

            <!-- Estado vacío cuando no hay talleres -->
            <div x-show="allWorkshops.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay talleres disponibles</h3>
                <p class="text-gray-500">Contacta al administrador para crear talleres</p>
            </div>
        </div>

        <!-- Controles de paginación -->
        <div x-show="totalPages > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center text-sm text-gray-500">
                <span>Mostrando página </span>
                <span x-text="currentPage" class="font-medium text-gray-900 mx-1"></span>
                <span> de </span>
                <span x-text="totalPages" class="font-medium text-gray-900 mx-1"></span>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Botón anterior -->
                <button 
                    @click.stop="previousPage()"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md transition-colors"
                    type="button"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <!-- Números de página -->
                <template x-for="page in getPageNumbers()" :key="page">
                    <button 
                        @click.stop="goToPage(page)"
                        :class="currentPage === page ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
                        class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                        type="button"
                        x-text="page"
                    ></button>
                </template>
                
                <!-- Botón siguiente -->
                <button 
                    @click.stop="nextPage()"
                    :disabled="currentPage === totalPages"
                    :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
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

    <!-- Campo oculto para sincronizar con Filament -->
    <input 
        type="hidden" 
        name="selected_workshops" 
        x-model="selectedWorkshopsJson"
        wire:model="data.selected_workshops"
    />
    
</div>

<script>
function workshopSelector() {
    return {
        // Datos iniciales
        allWorkshops: @json($workshops->values()->toArray()),
        selectedWorkshops: @json($workshops->where('selected', true)->pluck('id')->toArray()),
        
        // Estado de búsqueda y paginación
        searchQuery: '',
        filteredWorkshops: [],
        currentPage: 1,
        perPage: 6,
        
        init() {
            // Inicializar talleres filtrados con todos los talleres
            this.filteredWorkshops = [...this.allWorkshops];
        },
        
        // Computed properties
        get selectedCount() {
            return this.selectedWorkshops.length;
        },
        
        get selectedWorkshopsJson() {
            return JSON.stringify(this.selectedWorkshops);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredWorkshops.length / this.perPage);
        },
        
        get paginatedWorkshops() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredWorkshops.slice(start, end);
        },
        
        // Funciones de búsqueda
        updateSearch() {
            if (this.searchQuery.trim() === '') {
                this.filteredWorkshops = [...this.allWorkshops];
            } else {
                const query = this.searchQuery.toLowerCase().trim();
                this.filteredWorkshops = this.allWorkshops.filter(workshop => {
                    return workshop.name.toLowerCase().includes(query) ||
                           workshop.instructor.toLowerCase().includes(query) ||
                           workshop.day.toLowerCase().includes(query);
                });
            }
            
            // Reset a la primera página después de buscar
            this.currentPage = 1;
        },
        
        clearSearch() {
            this.searchQuery = '';
            this.updateSearch();
        },
        
        getResultsText() {
            if (this.searchQuery.length > 0) {
                return `${this.filteredWorkshops.length} talleres encontrados`;
            }
            return `${this.allWorkshops.length} talleres disponibles`;
        },
        
        // Función para seleccionar/deseleccionar talleres
        toggleWorkshop(workshopId) {
            const index = this.selectedWorkshops.indexOf(workshopId);
            if (index > -1) {
                this.selectedWorkshops.splice(index, 1);
            } else {
                this.selectedWorkshops.push(workshopId);
            }
            
            // Actualizar el campo oculto para que Filament detecte el cambio
            this.$nextTick(() => {
                const hiddenInput = document.querySelector('input[name="selected_workshops"]');
                if (hiddenInput) {
                    hiddenInput.value = this.selectedWorkshopsJson;
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        },
        
        // Funciones de paginación
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
                // Mostrar todas las páginas si son pocas
                for (let i = 1; i <= total; i++) {
                    pages.push(i);
                }
            } else {
                // Lógica más compleja para muchas páginas
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

<style>
.workshop-card {
    transition: all 0.2s ease-in-out;
}

.workshop-card:hover {
    transform: translateY(-2px);
}

/* Animación para el campo de búsqueda */
input[type="text"]:focus {
    transform: scale(1.02);
    transition: transform 0.2s ease-in-out;
}
</style>