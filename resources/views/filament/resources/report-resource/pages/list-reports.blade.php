<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Título del Dashboard -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Reporte de Indicadores Principales</h2>
            
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Taller</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option>Seleccionar</option>
                        <option>Programación Web con PHP</option>
                        <option>Diseño Gráfico Digital</option>
                        <option>Marketing Digital</option>
                        <option>Excel Avanzado</option>
                        <option>Inglés Básico</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Período temporal</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Fecha inicial">
                        <input type="date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Fecha final">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mes</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option>Seleccionar</option>
                        <option>Enero</option>
                        <option>Febrero</option>
                        <option>Marzo</option>
                        <option>Abril</option>
                        <option>Mayo</option>
                        <option>Junio</option>
                        <option>Julio</option>
                        <option>Agosto</option>
                        <option>Septiembre</option>
                        <option>Octubre</option>
                        <option>Noviembre</option>
                        <option>Diciembre</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Año</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option>Seleccionar</option>
                        <option>2024</option>
                        <option>2023</option>
                        <option>2022</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Métricas principales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Alumnos activos -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Alumnos activos</h3>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getActiveStudentsData()['total'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Número total</div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Período anterior</span>
                        <span class="text-sm font-medium flex items-center
                            {{ $this->getActiveStudentsData()['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $this->getActiveStudentsData()['percentage'] }}%
                            @if($this->getActiveStudentsData()['trend'] === 'up')
                                ↗
                            @else
                                ↘
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Casos de doble Pago -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Casos de doble Pago</h3>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getDoublePaysData()['total'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Número total</div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Período anterior</span>
                        <span class="text-sm font-medium flex items-center
                            {{ $this->getDoublePaysData()['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $this->getDoublePaysData()['percentage'] }}%
                            @if($this->getDoublePaysData()['trend'] === 'up')
                                ↗
                            @else
                                ↘
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Faltas sin justificar -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Faltas sin justificar</h3>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getUnexcusedAbsencesData()['total'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Número total</div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Período anterior</span>
                        <span class="text-sm font-medium flex items-center
                            {{ $this->getUnexcusedAbsencesData()['trend'] === 'up' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $this->getUnexcusedAbsencesData()['percentage'] }}%
                            @if($this->getUnexcusedAbsencesData()['trend'] === 'up')
                                ↗
                            @else
                                ↘
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Necesidades especiales -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Necesidades especiales</h3>
                <div class="space-y-2">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getSpecialNeedsData()['total'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Número total</div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Período anterior</span>
                        <span class="text-sm font-medium flex items-center
                            {{ $this->getSpecialNeedsData()['trend'] === 'up' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $this->getSpecialNeedsData()['percentage'] }}%
                            @if($this->getSpecialNeedsData()['trend'] === 'up')
                                ↗
                            @else
                                ↘
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Nro. de Alumnos por Categoría de Talleres -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Nro. de Alumnos por Categoría de Talleres
                </h3>
                <div class="space-y-4">
                    <!-- Leyenda -->
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-700 rounded-full mr-2"></div>
                            <span>Expresión Artística y Cultural</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span>Recreación y Vida Práctica</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <span>Bienestar Físico, Mental y espiritual</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                            <span>Acondicionamiento Corporal</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span>Tecnología y Aprendizaje Digital</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                            <span>Artes y Manualidades</span>
                        </div>
                    </div>
                    
                    <!-- Gráfico de barras -->
                    <div class="space-y-3">
                        @php
                            $categoriesData = $this->getWorkshopCategoriesData();
                            $maxValue = max($categoriesData);
                            $colors = [
                                'Expresión Artística y Cultural' => 'bg-green-700',
                                'Recreación y Vida Práctica' => 'bg-green-500',
                                'Bienestar Físico, Mental y espiritual' => 'bg-yellow-500',
                                'Acondicionamiento Corporal' => 'bg-orange-500',
                                'Tecnología y Aprendizaje Digital' => 'bg-red-500',
                                'Artes y Manualidades' => 'bg-blue-500'
                            ];
                        @endphp
                        
                        @foreach($categoriesData as $category => $count)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400 text-xs">{{ $category }}</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $count }}</span>
                                </div>
                                <div class="flex h-8 bg-gray-200 dark:bg-gray-700 rounded">
                                    @php
                                        $width = $maxValue > 0 ? ($count / $maxValue) * 100 : 0;
                                    @endphp
                                    <div class="{{ $colors[$category] }} rounded flex items-center justify-end pr-2" 
                                         style="width: {{ $width }}%">
                                        @if($width > 15)
                                            <span class="text-white text-xs font-medium">{{ $count }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
                        Nro. de Alumnos
                    </div>
                </div>
            </div>

            <!-- Gráficos de dona -->
            <div class="space-y-6">
                <!-- Asistencia Promedio -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Asistencia Promedio</h3>
                    <div class="flex items-center justify-center">
                        <div class="relative w-32 h-32">
                            @php
                                $attendanceData = $this->getAttendanceData();
                                $presentPercentage = $attendanceData['present_percentage'];
                                $absentPercentage = $attendanceData['absent_percentage'];
                                $circumference = 2 * 3.14159 * 45;
                                $presentStroke = ($presentPercentage / 100) * $circumference;
                                $absentStroke = ($absentPercentage / 100) * $circumference;
                            @endphp
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="transparent" stroke="#e5e7eb" stroke-width="8"/>
                                <circle cx="50" cy="50" r="45" fill="transparent" stroke="#10b981" stroke-width="8"
                                        stroke-dasharray="{{ $presentStroke }} {{ $circumference }}"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $attendanceData['present'] }}</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400">({{ $presentPercentage }}%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-600 rounded-full mr-2"></div>
                                <span class="text-sm">Presentes</span>
                            </div>
                            <span class="text-sm font-medium">{{ $attendanceData['present'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-gray-300 rounded-full mr-2"></div>
                                <span class="text-sm">Ausentes</span>
                            </div>
                            <span class="text-sm font-medium">{{ $attendanceData['absent'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pagos Pendientes -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pagos Pendientes</h3>
                    <div class="flex items-center justify-center">
                        <div class="relative w-32 h-32">
                            @php
                                $paymentsData = $this->getPendingPaymentsData();
                                $paidPercentage = $paymentsData['paid_percentage'];
                                $pendingPercentage = $paymentsData['pending_percentage'];
                                $paidStroke = ($paidPercentage / 100) * $circumference;
                            @endphp
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="transparent" stroke="#e5e7eb" stroke-width="8"/>
                                <circle cx="50" cy="50" r="45" fill="transparent" stroke="#10b981" stroke-width="8"
                                        stroke-dasharray="{{ $paidStroke }} {{ $circumference }}"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $paymentsData['paid'] }}</span>
                                <span class="text-xs text-gray-600 dark:text-gray-400">({{ $paidPercentage }}%)</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-600 rounded-full mr-2"></div>
                                <span class="text-sm">Pagados</span>
                            </div>
                            <span class="text-sm font-medium">{{ $paymentsData['paid'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-600 rounded-full mr-2"></div>
                                <span class="text-sm">Pendientes</span>
                            </div>
                            <span class="text-sm font-medium">{{ $paymentsData['pending'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
