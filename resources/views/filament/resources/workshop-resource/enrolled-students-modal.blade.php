<div class="space-y-4 pr-1">
    @if($students->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <p>No hay estudiantes inscritos en este taller para el período actual.</p>
        </div>
    @else
        <div class="flex gap-4 mb-4">
            <div class="flex items-center gap-2 text-sm">
                <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:#e0f2fe;color:#075985;">Pagado</span>
                <span class="text-gray-600">{{ $students->where('payment_status', 'completed')->count() }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:#fef3c7;color:#92400e;">Pendiente</span>
                <span class="text-gray-600">{{ $students->where('payment_status', 'pending')->count() }}</span>
            </div>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left pb-2 font-semibold text-gray-700">#</th>
                    <th class="text-left pb-2 font-semibold text-gray-700">Estudiante</th>
                    <th class="text-left pb-2 font-semibold text-gray-700">Código</th>
                    <th class="text-left pb-2 font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($students as $i => $enrollment)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 pr-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="py-2 pr-3 font-medium text-gray-900">
                            {{ $enrollment->student?->last_names }} {{ $enrollment->student?->first_names }}
                        </td>
                        <td class="py-2 pr-3 text-gray-500 font-mono text-xs">
                            {{ $enrollment->student?->student_code ?? '—' }}
                        </td>
                        <td class="py-2">
                            @if($enrollment->payment_status === 'completed')
                                <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:#e0f2fe;color:#075985;">
                                    Pagado
                                </span>
                            @else
                                <span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;background-color:#fef3c7;color:#92400e;">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
