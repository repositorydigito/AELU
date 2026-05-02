@if(empty($scheduleData))
    <div class="text-gray-500 italic p-4">Configure la fecha de inicio para generar el horario automáticamente</div>
@else
    @php
        $totalClasses = count($scheduleData);
        $totalColumns = 2 + $totalClasses;
        $gridStyle = "grid-template-columns: repeat({$totalColumns}, minmax(0, 1fr));";
        $dayOfWeekDisplay = is_array($daysOfWeek) ? implode('/', $daysOfWeek) : ($daysOfWeek ?: 'Lunes');
    @endphp

    <div class="border rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-gray-50 border-b">
            <div class="grid gap-px" style="{{ $gridStyle }}">
                <div class="p-3 font-semibold text-sm">Día</div>
                <div class="p-3 font-semibold text-sm">Nro. de Clases</div>
                @for($i = 1; $i <= $totalClasses; $i++)
                    <div class="p-3 font-semibold text-sm">Clase {{ $i }}</div>
                @endfor
            </div>
        </div>

        {{-- Data row --}}
        <div class="bg-white">
            <div class="grid gap-px border-b" style="{{ $gridStyle }}">
                <div class="p-3 text-sm">{{ $dayOfWeekDisplay }}</div>
                <div class="p-3 text-sm text-blue-600">{{ $totalClasses }} clases</div>
                @foreach($scheduleData as $class)
                    <div class="p-3 text-sm">{{ $class['date'] }}</div>
                @endforeach
            </div>
        </div>
    </div>
@endif
