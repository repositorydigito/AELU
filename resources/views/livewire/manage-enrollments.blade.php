<div>
    {{-- Resumen del Batch --}}
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 mb-4">
        <div class="grid grid-cols-4 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Total Inscripciones:</span>
                <span class="ml-2 font-semibold">{{ $enrollments->count() }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Pagadas:</span>
                <span class="ml-2 font-semibold text-green-600">{{ $enrollments->where('payment_status', 'completed')->count() }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Pendientes:</span>
                <span class="ml-2 font-semibold text-orange-600">{{ $enrollments->where('payment_status', 'pending')->count() }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Anuladas:</span>
                <span class="ml-2 font-semibold text-red-600">{{ $enrollments->where('payment_status', 'refunded')->count() }}</span>
            </div>
        </div>
    </div>

    {{-- Tabla de Inscripciones --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Taller
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Instructor
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Clases
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Monto
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($enrollments as $enrollment)
                    <tr class="hover:bg-gray-50">
                        {{-- Taller --}}
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $enrollment->instructorWorkshop->workshop->name ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                @php
                                    $dayNames = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
                                    $dayName = $dayNames[$enrollment->instructorWorkshop->day_of_week] ?? 'N/A';
                                    $startTime = $enrollment->instructorWorkshop->start_time
                                        ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->start_time)->format('H:i')
                                        : 'N/A';
                                    $endTime = $enrollment->instructorWorkshop->end_time
                                        ? \Carbon\Carbon::parse($enrollment->instructorWorkshop->end_time)->format('H:i')
                                        : 'N/A';
                                @endphp
                                {{ $dayName }} {{ $startTime }}-{{ $endTime }}
                            </div>
                        </td>

                        {{-- Instructor --}}
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                {{ $enrollment->instructorWorkshop->instructor->full_name ?? 'N/A' }}
                            </div>
                        </td>

                        {{-- Clases --}}
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $enrollment->number_of_classes }}
                            </div>
                        </td>

                        {{-- Monto --}}
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm font-semibold text-gray-900">
                                S/ {{ number_format($enrollment->total_amount, 2) }}
                            </div>
                        </td>

                        {{-- Estado --}}
                        <td class="px-4 py-3 text-center">
                            @if($enrollment->payment_status === 'refunded')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ✗ Anulado
                                </span>
                            @elseif($enrollment->payment_status === 'completed')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Pagado
                                </span>
                            @elseif($enrollment->payment_status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    ⏳ Pendiente
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    ? {{ ucfirst($enrollment->payment_status) }}
                                </span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="px-4 py-3 text-center">
                            @if($enrollment->payment_status === 'completed')
                                {{-- Mostrar tickets asociados --}}
                                @if($enrollment->tickets->isNotEmpty())
                                    <div class="flex flex-col gap-1 items-center">
                                        @foreach($enrollment->tickets as $ticket)
                                            <a
                                                href="{{ route('ticket.pdf', ['ticket' => $ticket->id]) }}"
                                                target="_blank"
                                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded hover:bg-blue-100"
                                                title="Ver ticket {{ $ticket->ticket_code }}"
                                            >
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                                </svg>
                                                {{ $ticket->ticket_code }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-500">Pagado</span>
                                @endif
                            @elseif($enrollment->payment_status === 'refunded')
                                <div class="text-xs text-gray-500">
                                    @if($enrollment->cancelled_at)
                                        Anulado el {{ $enrollment->cancelled_at->format('d/m/Y') }}
                                    @else
                                        Anulado
                                    @endif
                                </div>
                            @else
                                {{-- Botón de anular --}}
                                <x-filament::button
                                    wire:click="$dispatch('open-modal', { id: 'cancel-enrollment-{{ $enrollment->id }}' })"
                                    color="danger"
                                    size="sm"
                                    outlined
                                >
                                    Anular
                                </x-filament::button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($enrollments->where('payment_status', 'pending')->isEmpty())
        <div class="text-center py-4 text-sm text-gray-500">
            No hay inscripciones pendientes que se puedan anular.
        </div>
    @endif

    {{-- Modales de confirmación de anulación para cada inscripción --}}
    @foreach($enrollments->where('payment_status', 'pending') as $enrollment)
        <x-filament::modal id="cancel-enrollment-{{ $enrollment->id }}" width="2xl">
            <x-slot name="heading">
                Confirmar Anulación
            </x-slot>

            <div class="space-y-4">
                <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                    <p class="text-sm text-red-800">
                        ¿Estás seguro que deseas anular esta inscripción?
                    </p>
                </div>

                <div class="space-y-2 text-sm">
                    <div><strong>Taller:</strong> {{ $enrollment->instructorWorkshop->workshop->name ?? 'N/A' }}</div>
                    <div><strong>Monto:</strong> S/ {{ number_format($enrollment->total_amount, 2) }}</div>
                    <div><strong>Estado:</strong> Pendiente de pago</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo de anulación (opcional)
                    </label>
                    <textarea
                        wire:model="cancellationReason.{{ $enrollment->id }}"
                        rows="3"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Ingrese el motivo de la anulación..."
                    ></textarea>
                </div>
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    color="gray"
                    wire:click="$dispatch('close-modal', { id: 'cancel-enrollment-{{ $enrollment->id }}' })"
                >
                    Cancelar
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    wire:click="cancelEnrollment({{ $enrollment->id }})"
                >
                    Confirmar Anulación
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endforeach
</div>
