<x-filament-panels::page>
    <div class="space-y-6">
        @if(! $selectedStudentId)
            <x-filament::card>
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Buscar estudiante</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Busca por nombre, apellido o documento para revisar sus clases pagadas no asistidas.
                    </p>
                </div>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchName"
                    placeholder="Nombre, apellido o documento..."
                    autocomplete="off"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >

                @if(count($students) > 0)
                    <div class="mt-3 space-y-1">
                        @foreach($students as $student)
                            <button
                                type="button"
                                wire:click="selectStudent({{ $student['id'] }})"
                                class="w-full text-left px-4 py-2 border border-gray-200 rounded-md hover:bg-gray-50"
                            >
                                <span class="font-medium">{{ $student['full_name'] }}</span>
                                <span class="text-sm text-gray-500">{{ $student['document_number'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $selectedStudentData['full_name'] }}</h2>
                        <p class="text-sm text-gray-600">Selecciona el período de origen para revisar sus clases pagadas no asistidas.</p>
                    </div>
                    <x-filament::link wire:click="backToSearch" tag="button" color="gray" icon="heroicon-o-arrow-left" size="sm">
                        Cambiar estudiante
                    </x-filament::link>
                </div>

                <div class="mt-4">
                    <select
                        wire:change="selectPeriod($event.target.value)"
                        class="w-full md:w-80 px-4 py-2 border border-gray-300 rounded-md"
                    >
                        <option value="">-- Seleccionar período --</option>
                        @foreach($periods as $id => $label)
                            <option value="{{ $id }}" @selected($selectedPeriodId == $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filament::card>

            @if($selectedPeriodId)
                <x-filament::card>
                    <h3 class="text-md font-semibold text-gray-900 mb-3">Clases pagadas no asistidas</h3>

                    @if(count($candidates) === 0)
                        <p class="text-sm text-gray-600">No hay clases pagadas y no asistidas pendientes de recuperar en este período.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($candidates as $candidate)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $candidate['workshop_name'] }}</h4>
                                            <p class="text-sm text-gray-600">Instructor: {{ $candidate['instructor_name'] }}</p>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-700">Total inscripción: S/ {{ number_format($candidate['total_amount'], 2) }}</span>
                                    </div>

                                    <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                                        <thead>
                                            <tr style="border-bottom:1px solid #e5e7eb;">
                                                <th style="padding:0.4rem 0.5rem; text-align:left;">Recuperar</th>
                                                <th style="padding:0.4rem 0.5rem; text-align:left;">Fecha</th>
                                                <th style="padding:0.4rem 0.5rem; text-align:left;">Motivo</th>
                                                <th style="padding:0.4rem 0.5rem; text-align:right;">Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($candidate['missed_classes'] as $class)
                                                <tr style="border-bottom:1px solid #f3f4f6;">
                                                    <td style="padding:0.4rem 0.5rem;">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleClass({{ $candidate['enrollment_id'] }}, {{ $class['id'] }})"
                                                            @checked($selectedClasses[$candidate['enrollment_id']][$class['id']] ?? false)
                                                        >
                                                    </td>
                                                    <td style="padding:0.4rem 0.5rem;">{{ $class['class_date'] }}</td>
                                                    <td style="padding:0.4rem 0.5rem;">
                                                        <x-filament::badge :color="$class['origin'] === 'feriado' ? 'info' : 'warning'">
                                                            {{ $class['origin'] === 'feriado' ? 'Feriado' : 'Inasistencia' }}
                                                        </x-filament::badge>
                                                    </td>
                                                    <td style="padding:0.4rem 0.5rem; text-align:right;">S/ {{ number_format($class['class_fee'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::card>
            @endif

            @if(count($credits) > 0)
                <x-filament::card>
                    <h3 class="text-md font-semibold text-gray-900 mb-3">Créditos del estudiante</h3>

                    <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                        <thead>
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <th style="padding:0.4rem 0.5rem; text-align:left;">Taller</th>
                                <th style="padding:0.4rem 0.5rem; text-align:right;">Monto</th>
                                <th style="padding:0.4rem 0.5rem; text-align:left;">Vigente hasta</th>
                                <th style="padding:0.4rem 0.5rem; text-align:center;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($credits as $credit)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:0.4rem 0.5rem;">{{ $credit['workshop_name'] }}</td>
                                    <td style="padding:0.4rem 0.5rem; text-align:right;">S/ {{ number_format($credit['amount'], 2) }}</td>
                                    <td style="padding:0.4rem 0.5rem;">{{ $credit['valid_through'] }}</td>
                                    <td style="padding:0.4rem 0.5rem; text-align:center;">
                                        @php
                                            $color = match($credit['status']) {
                                                'available' => 'success',
                                                'consumed' => 'gray',
                                                'expired' => 'danger',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <x-filament::badge :color="$color">{{ ucfirst($credit['status']) }}</x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::card>
            @endif
        @endif
    </div>
</x-filament-panels::page>
