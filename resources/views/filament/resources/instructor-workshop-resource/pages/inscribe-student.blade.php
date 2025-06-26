<x-filament-panels::page>
    <div class="mb-6 p-4 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-bold mb-2">Horario Seleccionado</h2>
        <ul>
            <li><strong>Taller:</strong> {{ $record->workshop->name }}</li>
            <li><strong>Instructor:</strong> {{ $record->instructor->full_name_with_code ?? $record->instructor->full_name }}</li>
            <li><strong>Día:</strong> {{ ['0'=>'Domingo','1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado'][$record->day_of_week] ?? $record->day_of_week }}</li>
            <li><strong>Hora:</strong> {{ $record->start_time->format('h:i a') }} - {{ $record->end_time->format('h:i a') }}</li>
            <li><strong>Lugar:</strong> {{ $record->place }}</li>
            <li><strong>Tarifa mensual estándar:</strong> S/. {{ number_format($record->workshop->standard_monthly_fee, 2) }}</li>
            <li><strong>Cupos máximos:</strong> {{ $record->max_capacity }}</li>
        </ul>
    </div>

    <form wire:submit.prevent="inscribe">
        {{ $this->form }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" color="primary">Inscribir Alumno</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

