<x-filament::page>
    <div class="space-y-6">
        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form wire:submit.prevent="$refresh">
                {{ $this->form }}
                <div class="mt-4">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Actualizar Reporte</span>
                        <span wire:loading>Cargando...</span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Dashboard Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profesores Activos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Profesores activos</h3>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getActiveInstructorsData()['total'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Número total</div>
                    <div class="space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Por hora</span>
                            <span class="text-sm font-medium">{{ $this->getActiveInstructorsData()['hourly'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Voluntarios</span>
                            <span class="text-sm font-medium text-green-600">{{ $this->getActiveInstructorsData()['volunteers'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Distribución de profesores -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Distribución de profesores</h4>
                    <div class="relative">
                        <!-- Gráfico de dona simple con CSS -->
                        <div class="flex items-center justify-center">
                            <div class="relative w-24 h-24">
                                @php
                                    $volunteerPercentage = $this->getActiveInstructorsData()['volunteer_percentage'];
                                    $hourlyPercentage = $this->getActiveInstructorsData()['hourly_percentage'];
                                @endphp
                                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="16" fill="transparent" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="16" fill="transparent" stroke="#10b981" stroke-width="3"
                                            stroke-dasharray="{{ $volunteerPercentage }} {{ 100 - $volunteerPercentage }}"
                                            stroke-dashoffset="0"/>
                                    <circle cx="18" cy="18" r="16" fill="transparent" stroke="#d1d5db" stroke-width="3"
                                            stroke-dasharray="{{ $hourlyPercentage }} {{ 100 - $hourlyPercentage }}"
                                            stroke-dashoffset="-{{ $volunteerPercentage }}"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="text-xs text-gray-600 dark:text-gray-400">{{ $volunteerPercentage }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-gray-600 dark:text-gray-400">Voluntarios</span>
                            </div>
                            <div class="flex items-center text-xs">
                                <div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div>
                                <span class="text-gray-600 dark:text-gray-400">Por Horas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cantidad de Horas dictadas por Tipo de Profesor -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Cantidad de Horas dictadas por Tipo de Profesor
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-600 rounded-full mr-2"></div>
                            <span>Voluntarios</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-300 rounded-full mr-2"></div>
                            <span>Por Horas</span>
                        </div>
                    </div>
                    
                    <!-- Gráfico de barras simple -->
                    <div class="space-y-3">
                        @foreach($this->getHoursData() as $weekName => $weekData)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $weekName }}</span>
                                    <span class="text-gray-900 dark:text-white">{{ $weekData['volunteers'] + $weekData['hourly'] }}h</span>
                                </div>
                                <div class="flex h-6 bg-gray-200 dark:bg-gray-700 rounded">
                                    @php
                                        $total = $weekData['volunteers'] + $weekData['hourly'];
                                        $volunteerWidth = $total > 0 ? ($weekData['volunteers'] / $total) * 100 : 0;
                                        $hourlyWidth = $total > 0 ? ($weekData['hourly'] / $total) * 100 : 0;
                                    @endphp
                                    <div class="bg-green-600 rounded-l" style="width: {{ $volunteerWidth }}%"></div>
                                    <div class="bg-green-300 rounded-r" style="width: {{ $hourlyWidth }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Nro. de Talleres dictados por Profesor -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Nro. de Talleres dictados por Profesor
            </h3>
            <div class="space-y-3">
                @foreach($this->getInstructorWorkshopsData() as $data)
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $data['instructor'] }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Talleres</span>
                                <div class="flex space-x-2">
                                    @foreach($data['workshops'] as $workshop => $count)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ $workshop }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="bg-green-600 h-4 rounded-full" style="width: {{ min(($data['total'] / 10) * 100, 100) }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $data['total'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::page>
