<x-filament::page>
    <form wire:submit.prevent="saveAttendance">
        {{ $this->form }}

        <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
            <table class="w-full whitespace-nowrap divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Alumno</th>
                        @foreach ($this->getDaysInMonth() as $day)
                            <th class="px-3 py-3 text-center">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($this->enrollments as $enrollment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $enrollment->student->full_name }}
                            </td>
                            @foreach ($this->getDaysInMonth() as $day)
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                    <input
                                        type="checkbox"
                                        wire:model="attendanceData.{{ $enrollment->id }}.{{ $day }}"
                                        class="form-checkbox h-5 w-5 text-primary-600 transition duration-150 ease-in-out"
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->getDaysInMonth()) + 1 }}" class="px-6 py-4 text-center text-sm text-gray-500">
                                No hay alumnos inscritos en este horario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                Guardar Asistencia
            </x-filament::button>
        </div>
    </form>
</x-filament::page>