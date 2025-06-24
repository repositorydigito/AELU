<div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $workshop->name }}</h3>
    <div class="flex items-center gap-2 mt-2">
        <x-heroicon-o-user-group class="w-5 h-5 text-primary-600" />
        <span>{{ $workshop->enrolledCount() }}/{{ $workshop->max_students }}</span>
    </div>
    <p class="text-sm text-gray-600 dark:text-gray-400">Instructor: {{ $workshop->instructor->first_names . ' ' . $workshop->instructor->last_names  }} </p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Día: {{ $workshop->weekday }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Horario: {{ $startTime }} - {{ $endTime }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Fecha inicio: {{ $workshop->start_date->format('d/m/Y') }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-400">Fecha fin: {{ $workshop->end_date->format('d/m/Y') }}</p>
    <p class="text-base font-bold text-primary-600 dark:text-primary-400">Tarifa Mensual: S/. {{ $workshop->final_monthly_fee }}</p>
</div>
