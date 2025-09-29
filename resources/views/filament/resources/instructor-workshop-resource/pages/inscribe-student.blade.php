<x-filament-panels::page>
    <div class="mb-6 p-4 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-bold mb-2">Horario Seleccionado</h2>
        <ul>
            <li><strong>Taller:</strong> {{ $record->workshop->name }}</li>
            <li><strong>Modalidad:</strong> {{ $record->workshop->modality ?? 'No especificada' }}</li>
            <li><strong>Instructor:</strong> {{ $record->instructor->first_names }} {{ $record->instructor->last_names }} </li>
            <li><strong>Día:</strong> {{ ['0'=>'Domingo','1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado'][$record->day_of_week] ?? $record->day_of_week }}</li>
            <li><strong>Hora:</strong> {{ $record->start_time->format('h:i a') }} - {{ $record->end_time->format('h:i a') }}
                @if($record->duration_hours)
                    ({{ $record->duration_hours }} horas)
                @endif
            </li>
            <li><strong>Lugar:</strong> {{ $record->place }}</li>
            <li><strong>Tarifa mensual estándar:</strong> S/. {{ number_format($record->workshop->standard_monthly_fee, 2) }}</li>
            <li><strong>Cupos máximos:</strong> {{ $record->max_capacity }}</li>
            
            {{-- Información sobre el tipo de instructor --}}
            <li><strong>Tipo de Instructor:</strong> 
                @if($record->payment_type === 'volunteer')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Voluntario ({{ $record->custom_volunteer_percentage ? number_format($record->custom_volunteer_percentage * 100, 1) . '% personalizado' : 'Porcentaje mensual' }})
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Por Horas (S/ {{ number_format($record->hourly_rate, 2) }}/hora)
                    </span>
                @endif
            </li>
            
            {{-- Información adicional para instructores por horas --}}
            @if($record->payment_type === 'hourly' && $record->duration_hours && $record->hourly_rate)
                <li><strong>Pago por clase del instructor:</strong> 
                    S/ {{ number_format($record->hourly_rate * $record->duration_hours, 2) }}
                    <small class="text-gray-600">({{ $record->duration_hours }} horas × S/ {{ number_format($record->hourly_rate, 2) }})</small>
                </li>
            @endif
        </ul>
    </div>

    {{-- Nueva sección: Información de tarifas diferenciadas --}}
    <div class="mb-6 p-4 bg-purple-50 rounded shadow border border-purple-200">
        <h3 class="text-md font-semibold mb-2 text-purple-800">🏷️ Sistema de Tarifas Diferenciadas</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="space-y-2">
                <h4 class="font-semibold text-purple-700">Tarifas según Categoría:</h4>
                <div class="space-y-1">
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Individual PRE-PAMA (< 60 años):</span>
                        <span class="text-blue-600 font-bold">+50%</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Individual (60-64 años):</span>
                        <span class="text-green-600 font-bold">Normal</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Transitorio Individual (65+ años):</span>
                        <span class="text-green-600 font-bold">Normal</span>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <h4 class="font-semibold text-purple-700">Exonerados de Pago:</h4>
                <div class="space-y-1">
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Transitorio Mayor de 75:</span>
                        <span class="text-red-600 font-bold">S/ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Hijo de Fundador:</span>
                        <span class="text-red-600 font-bold">S/ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center p-2 bg-white rounded border">
                        <span>Vitalicios:</span>
                        <span class="text-red-600 font-bold">S/ 0.00</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 p-2 bg-amber-100 rounded text-xs text-amber-800">
            <strong>Nota:</strong> La tarifa se calcula automáticamente según la categoría del estudiante seleccionado.
        </div>
    </div>

    {{-- Información sobre tarifas base del taller --}}
    @if($record->workshop->workshopPricings->count() > 0)
        <div class="mb-6 p-4 bg-blue-50 rounded shadow">
            <h3 class="text-md font-semibold mb-2 text-blue-800">Tarifas Base del Taller</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                @foreach($record->workshop->workshopPricings->where('for_volunteer_workshop', $record->payment_type === 'volunteer')->sortBy('number_of_classes') as $pricing)
                    <div class="text-center p-2 bg-white rounded border {{ $pricing->is_default ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                        <div class="font-bold text-lg">{{ $pricing->number_of_classes }}</div>
                        <div class="text-xs text-gray-600">{{ $pricing->number_of_classes == 1 ? 'clase' : 'clases' }}</div>
                        <div class="font-semibold text-green-600">S/ {{ number_format($pricing->price, 2) }}</div>
                        @if($pricing->is_default)
                            <div class="text-xs text-blue-600 font-medium">Recomendado</div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-blue-600 mt-2">
                *Tarifas base para {{ $record->payment_type === 'volunteer' ? 'instructor voluntario' : 'instructor por horas' }}. 
                Se aplicarán modificaciones según la categoría del estudiante.
            </p>
        </div>
    @else
        <div class="mb-6 p-4 bg-red-50 rounded shadow border border-red-200">
            <h3 class="text-md font-semibold mb-2 text-red-800">⚠️ Problema de Configuración</h3>
            <p class="text-red-700">No hay tarifas configuradas para este taller. Contacta al administrador para configurar las tarifas antes de realizar inscripciones.</p>
        </div>
    @endif

    <form wire:submit.prevent="inscribe">
        {{ $this->form }}
        
        {{-- Información dinámica del estudiante seleccionado --}}
        <div class="mt-4 p-4 bg-green-50 rounded border border-green-200" x-data="{ studentInfo: null }" x-init="
            $wire.on('student-selected', (data) => {
                studentInfo = data;
            });
        ">
            <h4 class="font-semibold text-green-800 mb-2">📋 Información del Estudiante Seleccionado</h4>
            <div id="student-pricing-info" class="text-sm text-green-700">
                Selecciona un estudiante para ver su información de tarifa...
            </div>
        </div>
        
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" color="primary">Inscribir Alumno</x-filament::button>
        </div>
    </form>

    <script>
        document.addEventListener('livewire:initialized', () => {           
            // Escuchar el evento 'open-pdf-ticket'
            Livewire.on('open-pdf-ticket', (event) => {
                const url = event.url;
                if (url) {
                    window.open(url, '_blank');
                } else {
                    console.error('Error: URL del PDF no proporcionada.');
                }
            });
        });
        
    </script>
</x-filament-panels::page>