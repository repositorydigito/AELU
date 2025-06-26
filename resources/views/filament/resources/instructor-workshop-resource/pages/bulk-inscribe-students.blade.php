<x-filament-panels::page>
    <form wire:submit.prevent="inscribe">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold mb-1">Alumno</label>
                    <select wire:model="data.student_id" class="filament-forms-input w-full">
                        <option value="">Seleccione...</option>
                        @foreach(\App\Models\Student::all() as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }} - {{ $student->student_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Periodo mensual</label>
                    <select wire:model="data.monthly_period_id" class="filament-forms-input w-full">
                        <option value="">Seleccione...</option>
                        @foreach(\App\Models\MonthlyPeriod::all() as $period)
                            <option value="{{ $period->id }}">{{ $period->year }} - {{ \Illuminate\Support\Carbon::create()->month($period->month)->monthName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-8">
                @foreach($workshops as $workshop)
                    @php $wid = $workshop->id; @endphp
                    <div class="p-4 border rounded-lg bg-white shadow-sm">
                        <div class="mb-2 font-bold text-lg text-primary-700">
                            {{ $workshop->workshop->name }} ({{ $workshop->instructor->full_name }})
                        </div>
                        <div class="mb-2 text-sm text-gray-600">
                            Día: <b>{{ [0=>'Domingo',1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'][$workshop->day_of_week] }}</b> |
                            Hora: <b>{{ \Carbon\Carbon::parse($workshop->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($workshop->end_time)->format('H:i') }}</b>
                        </div>
                        <div class="mb-2">
                            <label class="font-semibold">Tipo de inscripción:</label>
                            <select wire:model="data.workshops.{{ $wid }}.enrollment_type" class="filament-forms-input">
                                <option value="full_month">Mes completo</option>
                                <option value="specific_classes">Clases específicas</option>
                            </select>
                        </div>
                        @php $enrollmentType = data_get($data, 'workshops.' . $wid . '.enrollment_type', null); @endphp
                        @if($enrollmentType === 'full_month' || $enrollmentType === null)
                            <div class="mb-2">
                                <label class="font-semibold">Clases asignadas automáticamente:</label>
                                <ul class="list-disc ml-6">
                                    @foreach($this->getWorkshopClasses($wid) as $class)
                                        <li>{{ $class->class_date->format('d/m/Y') }} {{ $class->start_time->format('H:i') }} - {{ $class->end_time->format('H:i') }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif($enrollmentType === 'specific_classes')
                            <div class="mb-2">
                                <label class="font-semibold">Selecciona las clases:</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($this->getWorkshopClasses($wid) as $class)
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="data.workshops.{{ $wid }}.selected_classes" value="{{ $class->id }}">
                                            <span>{{ $class->class_date->format('d/m/Y') }} {{ $class->start_time->format('H:i') }} - {{ $class->end_time->format('H:i') }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <x-filament::button type="submit" color="primary">
                    Inscribir Alumno
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
