<x-filament-panels::page>
    <div class="mb-8 max-w-md">
        <label class="block font-bold mb-2">Selecciona el alumno a inscribir:</label>
        <select wire:model="selectedStudent" class="w-full rounded border-gray-300">
            <option value="">-- Selecciona un alumno --</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->full_name }} - {{ $student->student_code }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($workshops as $workshop)
            <div class="bg-white rounded-lg shadow p-4 flex flex-col gap-2">
                <h3 class="font-bold text-lg mb-2">{{ $workshop->workshop->name }}</h3>
                <div class="text-sm text-gray-500 mb-1">Instructor: {{ $workshop->instructor->full_name }}</div>
                <div class="text-sm text-gray-500 mb-1">Día: {{ ['0'=>'Domingo','1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado'][$workshop->day_of_week] ?? $workshop->day_of_week }}</div>
                <div class="text-sm text-gray-500 mb-1">Horario: {{ $workshop->start_time->format('H:i') }} - {{ $workshop->end_time->format('H:i') }}</div>
                <div class="font-bold text-green-700 mt-2">Tarifa Mensual: S/. {{ number_format($workshop->workshop->standard_monthly_fee, 2) }}</div>

                {{-- Periodo mensual --}}
                <label class="block mt-2 text-sm font-semibold">Periodo mensual:</label>
                <select wire:model.live="enrollmentData.{{ $workshop->id }}.period_id" class="w-full rounded border-gray-300">
                    @foreach(\App\Models\MonthlyPeriod::orderBy('start_date')->get() as $period)
                        <option value="{{ $period->id }}">{{ $period->year }} - {{ \Carbon\Carbon::createFromDate($period->year, $period->month)->translatedFormat('F') }}</option>
                    @endforeach
                </select>

                {{-- Tipo de inscripción --}}
                <label class="block mt-2 text-sm font-semibold">Tipo de inscripción:</label>
                <select wire:model.live="enrollmentData.{{ $workshop->id }}.type" class="w-full rounded border-gray-300">
                    <option value="full_month">Mes completo</option>
                    <option value="specific_classes">Clases específicas</option>
                </select>

                {{-- Sección de selección de clases --}}
                <label class="block mt-2 text-sm font-semibold">Clases del periodo:</label>
                <div class="flex flex-col gap-1">
                    @php
                        // Filter classes by the currently selected monthly period for this workshop
                        $currentPeriodClasses = $workshop->classes->where('monthly_period_id', $enrollmentData[$workshop->id]['period_id']);
                        $enrollmentType = $enrollmentData[$workshop->id]['type'] ?? 'full_month';
                    @endphp

                    @forelse($currentPeriodClasses as $class)
                        @if($enrollmentType === 'specific_classes')
                            {{-- Show checkboxes only if 'Clases específicas' is selected --}}
                            <label class="inline-flex items-center gap-x-2">
                                <input type="checkbox"
                                    wire:model="enrollmentData.{{ $workshop->id }}.classes"
                                    value="{{ $class->id }}"
                                    class="rounded border-gray-300"
                                >
                                {{ $class->class_date->format('d/m/Y') }} {{ $class->start_time->format('h:i a') }} - {{ $class->end_time->format('h:i a') }}
                            </label>
                        @else
                            {{-- Display as plain text if 'Mes completo' is selected --}}
                            <div class="pl-6 text-gray-700">
                                {{ $class->class_date->format('d/m/Y') }} {{ $class->start_time->format('h:i a') }} - {{ $class->end_time->format('h:i a') }}
                            </div>
                        @endif
                    @empty
                        <div class="text-gray-400 italic">No hay clases disponibles para este periodo.</div>
                    @endforelse
                </div>

                {{-- Estado de pago --}}
                <label class="block mt-2 text-sm font-semibold">Estado de pago:</label>
                <select wire:model="enrollmentData.{{ $workshop->id }}.payment_status" class="w-full rounded border-gray-300">
                    <option value="pending">Pendiente</option>
                    <option value="paid">Pagado</option>
                </select>
            </div>
        @endforeach
    </div>

    <div class="mt-8 flex justify-end">
        <x-filament::button wire:click="bulkInscribe" color="primary">
            Inscribir Alumno en Todos los Horarios Seleccionados
        </x-filament::button>
    </div>
</x-filament-panels::page>