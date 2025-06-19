<x-filament::page>
    <form wire:submit.prevent="import">
        {{ $this->form }}

        <div class="flex justify-start mt-6">
            <x-filament::button type="submit" color="primary">
                Importar Estudiantes
            </x-filament::button>
        </div>
    </form>
</x-filament::page>