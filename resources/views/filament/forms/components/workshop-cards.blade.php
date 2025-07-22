@php
    $workshops = $getViewData()['workshops'] ?? collect();
    $studentId = $getViewData()['student_id'] ?? null;
    $student = $studentId ? \App\Models\Student::find($studentId) : null;
    $isMaintenancePaid = $student ? $student->monthly_maintenance_paid : false;
    $perPage = 6;
    $totalWorkshops = $workshops->count();
    $totalPages = ceil($totalWorkshops / $perPage);
@endphp

<div x-data="workshopSelector({{ $totalPages }})" class="space-y-4">
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
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Talleres Disponibles</h3>
                <div class="text-sm text-gray-500">
                    <span x-text="selectedCount"></span> talleres seleccionados
                </div>
            </div>
            <p class="text-sm text-gray-600 mt-1">Haz clic en los talleres que deseas seleccionar para la inscripción</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
            @foreach($workshops as $index => $workshop)
                @php
                    $pageNumber = floor($index / $perPage) + 1;
                @endphp
                <div 
                    class="workshop-card border rounded-lg p-4 cursor-pointer transition-all duration-200 hover:shadow-md"
                    :class="selectedWorkshops.includes({{ $workshop['id'] }}) ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200' : 'border-gray-200 bg-white hover:border-gray-300'"
                    @click="toggleWorkshop({{ $workshop['id'] }})"
                    x-show="currentPage === {{ $pageNumber }}"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                >
                    <!-- Header del taller -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 text-lg">{{ $workshop['name'] }}</h4>
                            <div class="flex items-center mt-1">
                                <svg class="w-4 h-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="text-sm text-gray-600">{{ $workshop['instructor'] }}</span>
                            </div>
                        </div>
                        <div class="ml-2">
                            <div 
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="selectedWorkshops.includes({{ $workshop['id'] }}) ? 'border-primary-500 bg-primary-500' : 'border-gray-300'"
                            >
                                <svg 
                                    class="w-4 h-4 text-white" 
                                    :class="selectedWorkshops.includes({{ $workshop['id'] }}) ? 'block' : 'hidden'"
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
                            <span class="text-sm text-gray-700 font-medium">{{ $workshop['day'] }}</span>
                        </div>
                        <div class="flex pb-4 items-center">
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-gray-700">{{ $workshop['start_time'] }} - {{ $workshop['end_time'] }}</span>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="border-t pt-3 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Precio:</span>
                            <span class="text-sm font-semibold text-gray-900">S/ {{ number_format($workshop['price'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Número de clases:</span>
                            <span class="text-sm font-medium text-gray-700">{{ $workshop['max_classes'] }} clases</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Controles de paginación -->
        @if($totalPages > 1)
            <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div class="flex items-center text-sm text-gray-500">
                    <span>Mostrando página </span>
                    <span x-text="currentPage" class="font-medium text-gray-900 mx-1"></span>
                    <span> de {{ $totalPages }} </span>
                    <span class="ml-2"> ({{ $totalWorkshops }} talleres en total)</span>
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
                    @for($i = 1; $i <= $totalPages; $i++)
                        <button 
                            @click.stop="goToPage({{ $i }})"
                            :class="currentPage === {{ $i }} ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
                            class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                            type="button"
                        >
                            {{ $i }}
                        </button>
                    @endfor
                    
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

        @if($workshops->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-lg">No hay talleres disponibles</p>
                <p class="text-sm">Contacta al administrador para crear talleres</p>
            </div>
        @endif
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
function workshopSelector(totalPages) {
    return {
        selectedWorkshops: @json($workshops->where('selected', true)->pluck('id')->toArray()),
        currentPage: 1,
        totalPages: totalPages,
        
        get selectedCount() {
            return this.selectedWorkshops.length;
        },
        
        get selectedWorkshopsJson() {
            return JSON.stringify(this.selectedWorkshops);
        },
        
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
</style>