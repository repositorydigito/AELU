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
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse">
                            <thead>
                                <tr style="background-color: #f3f4f6;">
                                    <th class="border border-gray-300 px-2 py-1 text-left font-semibold text-gray-700">Taller</th>
                                    <th class="border border-gray-300 px-2 py-1 text-center font-semibold text-gray-700">Horario</th>
                                    <th class="border border-gray-300 px-2 py-1 text-center font-semibold text-gray-700">Clases</th>
                                    <th class="border border-gray-300 px-2 py-1 text-center font-semibold text-gray-700">Fechas</th>
                                    <th class="border border-gray-300 px-2 py-1 text-right font-semibold text-gray-700">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticket->studentEnrollments as $enrollment)
                                    @php
                                        $iw = $enrollment->instructorWorkshop;
                                        $workshop = $iw->workshop ?? null;

                                        $dayAbbreviations = [
                                            'Lunes' => 'LUN', 'Martes' => 'MAR', 'Miércoles' => 'MIE',
                                            'Jueves' => 'JUE', 'Viernes' => 'VIE', 'Sábado' => 'SAB', 'Domingo' => 'DOM',
                                        ];

                                        $daysOfWeek = $iw->day_of_week ?? [];
                                        if (is_array($daysOfWeek) && count($daysOfWeek) > 1) {
                                            $dayLabel = implode('/', array_map(fn($d) => $dayAbbreviations[$d] ?? ucfirst(substr($d, 0, 3)), $daysOfWeek));
                                        } else {
                                            $singleDay = is_array($daysOfWeek) ? ($daysOfWeek[0] ?? null) : $daysOfWeek;
                                            $dayLabel = $singleDay ?? 'N/A';
                                        }

                                        $startTime = $iw->start_time ? \Carbon\Carbon::parse($iw->start_time)->format('H:i') : '--:--';
                                        $endTime   = $iw->end_time   ? \Carbon\Carbon::parse($iw->end_time)->format('H:i')   : '--:--';

                                        $modality = match($workshop->modality ?? '') {
                                            'Presencial' => 'P',
                                            default      => 'V',
                                        };

                                        $classDates = $enrollment->enrollmentClasses
                                            ->filter(fn($ec) => $ec->workshopClass !== null)
                                            ->sortBy(fn($ec) => $ec->workshopClass->class_date)
                                            ->map(fn($ec) => \Carbon\Carbon::parse($ec->workshopClass->class_date)->format('d/m'))
                                            ->values()
                                            ->toArray();

                                        if (empty($classDates) && $workshop) {
                                            $wClasses = \App\Models\WorkshopClass::where('workshop_id', $workshop->id)
                                                ->where('monthly_period_id', $enrollment->monthly_period_id)
                                                ->where('status', '!=', 'cancelled')
                                                ->orderBy('class_date')
                                                ->take($enrollment->number_of_classes)
                                                ->get();
                                            $classDates = $wClasses->map(fn($wc) => \Carbon\Carbon::parse($wc->class_date)->format('d/m'))->toArray();
                                        }

                                        $isCancelled = !is_null($enrollment->cancelled_at);
                                    @endphp
                                    <tr style="{{ $isCancelled ? 'background-color:#fff1f2; text-decoration:line-through; color:#9ca3af;' : '' }}">
                                        <td class="border border-gray-300 px-2 py-1 font-semibold">
                                            {{ strtoupper($workshop->name ?? 'N/A') }}
                                            <span class="font-normal text-gray-500">({{ $modality }})</span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center leading-tight">
                                            <span class="font-semibold">{{ $dayLabel }}</span><br>
                                            <span class="text-gray-600">{{ $startTime }}-{{ $endTime }}</span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center font-semibold">
                                            {{ $enrollment->number_of_classes }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-center text-gray-600" style="min-width:90px;">
                                            @if(!empty($classDates))
                                                {{ implode(' · ', $classDates) }}
                                            @else
                                                <span class="text-gray-400">–</span>
                                            @endif
                                        </td>
                                        <td class="border border-gray-300 px-2 py-1 text-right font-semibold">
                                            S/ {{ number_format($enrollment->total_amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        (P) Presencial &nbsp;·&nbsp; (V) Virtual
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
