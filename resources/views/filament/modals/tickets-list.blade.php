<div class="space-y-4">
    @if($tickets->isEmpty())
        <div class="text-center py-8 text-gray-500">
            No hay tickets activos para esta inscripción.
        </div>
    @else
        @foreach($tickets as $ticket)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-lg">{{ $ticket->ticket_code }}</span>
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                {{ $ticket->formatted_status }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <strong>Emitido:</strong> {{ $ticket->issued_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="text-sm text-gray-600">
                            <strong>Por:</strong> {{ $ticket->issued_by_name }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-green-600">
                            S/ {{ number_format($ticket->total_amount, 2) }}
                        </div>
                    </div>
                </div>

                {{-- Talleres --}}
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="text-sm font-medium text-gray-700 mb-2">
                        Talleres incluidos ({{ $ticket->studentEnrollments->count() }}):
                    </div>
                    <div class="space-y-1">
                        @foreach($ticket->studentEnrollments as $enrollment)
                            <div class="text-sm text-gray-600">
                                ✓ {{ $enrollment->instructorWorkshop->workshop->name ?? 'N/A' }} - S/ {{ number_format($enrollment->total_amount, 2) }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Botón de descarga --}}
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <x-filament::button
                        tag="a"
                        :href="route('ticket.pdf', ['ticket' => $ticket->id])"
                        target="_blank"
                        color="primary"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Descargar PDF
                    </x-filament::button>
                </div>
            </div>
        @endforeach
    @endif
</div>
