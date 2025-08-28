<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Seleccionar Período Mensual
            </x-slot>
            <x-slot name="description">
                Seleccione un período mensual para ver todas las inscripciones de ese mes
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Resumen estadístico -->
        @if(!empty($monthlyEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Resumen - {{ $this->generatePeriodName($periodData->month, $periodData->year) }}
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryData['total_enrollments'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Inscripciones</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryData['total_students'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Estudiantes</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryData['total_workshops'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Talleres</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">S/ {{ number_format($summaryData['total_amount'] ?? 0, 2) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Monto Total</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryData['cash_payments'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pago Efectivo</div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summaryData['link_payments'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pago Link</div>
                </div>
            </div>
        </x-filament::section>
        @endif

        <!-- Tabla de inscripciones -->
        @if(!empty($monthlyEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Inscripciones del Período
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estudiante</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taller</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Instructor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Inscripción</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Clases</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método Pago</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Modalidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cajero</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($monthlyEnrollments as $enrollment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['student_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['student_document'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['workshop_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['instructor_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['enrollment_date'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['number_of_classes'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">S/ {{ number_format($enrollment['total_amount'], 2) }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['payment_method'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['modality'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['payment_document'] }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $enrollment['cashier_name'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        @if(empty($monthlyEnrollments) && $selectedPeriod)
        <x-filament::section>
            <div class="text-center py-8">
                <x-heroicon-o-calendar-days class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay inscripciones</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este período no tiene inscripciones registradas.</p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
