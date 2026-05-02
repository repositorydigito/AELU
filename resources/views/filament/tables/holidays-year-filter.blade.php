<div class="px-4 pt-4 pb-1">
    <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-600">Año</span>
        <select
            wire:model.live="filterYear"
            class="rounded-lg border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-500"
        >
            @foreach ($years as $value => $label)
                <option value="{{ $value }}" @selected($filterYear == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
