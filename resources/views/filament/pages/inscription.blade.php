<x-filament-panels::page>
    <form wire:submit.prevent="submitForm">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" form="submitForm" class="w-full">Confirmar Inscripción y Pagar</x-filament::button>               
        </div>        
    </form>
    
</x-filament-panels::page>

{{-- <x-filament-panels::page>
    <form wire:submit.prevent="submitForm">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" form="submitForm" class="w-full">Confirmar Inscripción y Pagar</x-filament::button>               
        </div>        
    </form>

    <section class="mt-10">
        <h2 class="text-xl font-bold mb-4">Catálogo de Talleres</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($this->paginatedWorkshops as $workshop)
                <div class="bg-white rounded-lg shadow p-4 flex flex-col justify-between">
                    @include('filament.forms.components.workshop-details', [
                        'workshop' => $workshop,
                        'startTime' => \Carbon\Carbon::parse($workshop->start_time)->format('h:i A'),
                        'endTime' => \Carbon\Carbon::parse($workshop->end_time)->format('h:i A'),
                    ])
                </div>
            @endforeach
        </div>
        <div class="mt-6 flex justify-center">
            {{ $this->paginatedWorkshops->links() }}
        </div>
    </section>
</x-filament-panels::page> --}}