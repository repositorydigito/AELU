<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4">

        <x-filament::section class="rounded-xl shadow-lg">
            <x-slot name="heading">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Inscripciones</h2>
            </x-slot>
            <ul class="space-y-3 text-gray-700 dark:text-gray-300">
                <li><a href="{{ url('/admin/enrollments-report1') }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-600 hover:underline transition">Inscripciones por alumno</a></li>
                <li><a href="{{ url('/admin/enrollments-report2') }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-600 hover:underline transition">Inscripciones por mes</a></li>
            </ul>
        </x-filament::section>

        <x-filament::section class="rounded-xl shadow-lg">
            <x-slot name="heading">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Profesores</h2>
            </x-slot>
            <ul class="space-y-3 text-gray-700 dark:text-gray-300">
                <li><a href="{{ url('/admin/instructor-report') }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-600 hover:underline transition">Por profesor</a></li>
            </ul>
        </x-filament::section>

    </div>
</x-filament-panels::page>
