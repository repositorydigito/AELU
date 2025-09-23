<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Formulario de filtros -->
        <x-filament::section>
            <x-slot name="heading">
                <h2 class="text-lg font-medium">Filtros</h2>
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        @if($selectedPeriod && (!empty($volunteerWorkshops) || !empty($hourlyWorkshops)))
            <!-- Resumen -->
            <x-filament::section>
                <x-slot name="heading">
                    <h2 class="text-lg font-medium">Resumen General</h2>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-medium text-blue-900">Talleres Voluntarios</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ $summary['volunteer_total_enrollments'] }}</p>
                        <p class="text-sm text-blue-700">Inscripciones</p>
                        <p class="text-lg font-semibold text-blue-800">S/ {{ number_format($summary['volunteer_total_amount'], 2) }}</p>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-medium text-green-900">Talleres por Horas</h3>
                        <p class="text-2xl font-bold text-green-600">{{ $summary['hourly_total_enrollments'] }}</p>
                        <p class="text-sm text-green-700">Inscripciones</p>
                        <p class="text-lg font-semibold text-green-800">S/ {{ number_format($summary['hourly_total_amount'], 2) }}</p>
                    </div>

                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-medium text-purple-900">Total General</h3>
                        <p class="text-2xl font-bold text-purple-600">{{ $summary['grand_total_enrollments'] }}</p>
                        <p class="text-sm text-purple-700">Inscripciones</p>
                        <p class="text-lg font-semibold text-purple-800">S/ {{ number_format($summary['grand_total_amount'], 2) }}</p>
                    </div>
                </div>
            </x-filament::section>

            @if(!empty($volunteerWorkshops))
                <!-- Talleres Voluntarios -->
                <x-filament::section>
                    <x-slot name="heading">
                        <h2 class="text-lg font-medium text-blue-700">Talleres Voluntarios</h2>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taller</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Inscripciones</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tarifa</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Recaudado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($volunteerWorkshops as $workshop)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['taller'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['horario'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['modalidad'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['instructor'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $workshop['inscripciones'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">S/ {{ number_format($workshop['tarifa'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">S/ {{ number_format($workshop['total_recaudado'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            @if(!empty($hourlyWorkshops))
                <!-- Talleres por Horas -->
                <x-filament::section>
                    <x-slot name="heading">
                        <h2 class="text-lg font-medium text-green-700">Talleres por Horas</h2>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taller</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modalidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Inscripciones</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tarifa</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Recaudado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($hourlyWorkshops as $workshop)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['taller'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['horario'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['modalidad'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $workshop['instructor'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $workshop['inscripciones'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">S/ {{ number_format($workshop['tarifa'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">S/ {{ number_format($workshop['total_recaudado'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

        @elseif($selectedPeriod)
            <!-- Sin datos -->
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Sin registros</h3>
                        <p class="mt-1 text-sm text-gray-500">No hay inscripciones registradas para el período seleccionado.</p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
