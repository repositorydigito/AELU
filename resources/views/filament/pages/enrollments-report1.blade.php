<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Formulario de selección -->
        <x-filament::section>
            <x-slot name="heading">
                Seleccionar Alumno
            </x-slot>
            <x-slot name="description">
                Busque y seleccione un alumno para ver su historial de inscripciones
            </x-slot>

            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Tabla de inscripciones -->
        @if(!empty($studentEnrollments))
        <x-filament::section>
            <x-slot name="heading">
                Historial de Inscripciones - {{ $studentData->first_names }} {{ $studentData->last_names }}
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documento</th>
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
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Este alumno no tiene inscripciones registradas.</p>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
