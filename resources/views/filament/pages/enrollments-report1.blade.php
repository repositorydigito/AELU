<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Búsqueda
            </x-slot>
            <x-slot name="description">
                Seleccione un alumno y opcionalmente un período específico para filtrar las inscripciones
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}

                @if($selectedStudent)
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            {{ $this->getFilterDescription() }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        <!-- Tabla de inscripciones -->
        @if(!empty($studentEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Historial de Inscripciones - {{ $studentData->first_names }} {{ $studentData->last_names }}
                @if($selectedPeriod)
                    @php
                        $period = \App\Models\MonthlyPeriod::find($selectedPeriod);
                        $periodName = $period ? $this->generatePeriodName($period->month, $period->year) : '';
                    @endphp
                    <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                        ({{ $periodName }})
                    </span>
                @endif
            </x-slot>

            <x-slot name="description">
                Total de inscripciones encontradas: {{ count($studentEnrollments) }}
                @if(count($studentEnrollments) > 0)
                    | Monto total: S/ {{ number_format(collect($studentEnrollments)->sum('total_amount'), 2) }}
                @endif
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taller</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Instructor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Inscripción</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Clases</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método de Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Modalidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cajero</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($studentEnrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['workshop_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['instructor_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['period_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['enrollment_date'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['number_of_classes'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['payment_method'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['modality'] ?? '' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['payment_document'] ?? '' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['cashier_name'] ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($studentEnrollments) && $selectedStudent)
        <x-filament::section>
            <div class="text-center py-8">
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay inscripciones</h3>
                @if($selectedPeriod)
                    @php
                        $period = \App\Models\MonthlyPeriod::find($selectedPeriod);
                        $periodName = $period ? $this->generatePeriodName($period->month, $period->year) : '';
                    @endphp
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Este alumno no tiene inscripciones registradas para el período {{ $periodName }}.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Este alumno no tiene inscripciones registradas.
                    </p>
                @endif
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
