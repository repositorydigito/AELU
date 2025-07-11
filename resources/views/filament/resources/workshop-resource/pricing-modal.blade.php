<div class="space-y-6">
    <!-- Información del taller -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $record->name }}</h3>
        @if($record->description)
            <p class="text-gray-600 text-sm mb-3">{{ $record->description }}</p>
        @endif
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Tarifa mensual estándar:</span>
                <span class="text-green-600 font-semibold">S/ {{ number_format($record->standard_monthly_fee, 2) }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Recargo aplicado:</span>
                <span class="text-blue-600 font-semibold">{{ $record->pricing_surcharge_percentage }}%</span>
            </div>
            {{-- <div>
                <span class="font-medium text-gray-700">Precio base por clase:</span>
                <span class="text-gray-600">S/ {{ number_format($record->standard_monthly_fee / 4, 2) }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Precio con recargo:</span>
                <span class="text-gray-600">S/ {{ number_format(($record->standard_monthly_fee / 4) * (1 + $record->pricing_surcharge_percentage / 100), 2) }}</span>
            </div> --}}
        </div>
    </div>

    <!-- Tarifas generadas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tarifas para instructores voluntarios -->
        <div class="border border-green-200 rounded-lg bg-green-50">
            <div class="bg-green-100 px-4 py-3 border-b border-green-200">
                <h4 class="font-semibold text-green-800 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Instructores Voluntarios
                </h4>                
            </div>
            <div class="p-4 space-y-3">
                @foreach($record->workshopPricings->where('for_volunteer_workshop', true)->sortBy('number_of_classes') as $pricing)
                    <div class="flex justify-between items-center p-3 bg-white rounded border {{ $pricing->is_default ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                        <div class="flex items-center">
                            <span class="font-medium text-gray-900">{{ $pricing->number_of_classes }}</span>
                            <span class="text-gray-600 ml-1">{{ $pricing->number_of_classes == 1 ? 'clase' : 'clases' }}</span>
                            
                            @if($pricing->is_default)
                                <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Estándar</span>
                            @endif
                        </div>
                        <span class="font-bold text-green-600 text-lg">S/ {{ number_format($pricing->price, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tarifas para instructores no voluntarios -->
        <div class="border border-blue-200 rounded-lg bg-blue-50">
            <div class="bg-blue-100 px-4 py-3 border-b border-blue-200">
                <h4 class="font-semibold text-blue-800 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    Instructores No Voluntarios
                </h4>                
            </div>
            <div class="p-4 space-y-3">
                @foreach($record->workshopPricings->where('for_volunteer_workshop', false)->sortBy('number_of_classes') as $pricing)
                    <div class="flex justify-between items-center p-3 bg-white rounded border {{ $pricing->is_default ? 'border-blue-300 bg-blue-50' : 'border-gray-200' }}">
                        <div class="flex items-center">
                            <span class="font-medium text-gray-900">{{ $pricing->number_of_classes }}</span>
                            <span class="text-gray-600 ml-1">{{ $pricing->number_of_classes == 1 ? 'clase' : 'clases' }}</span>
                            @if($pricing->is_default)
                                <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Estándar</span>
                            @endif
                        </div>
                        <span class="font-bold text-blue-600 text-lg">S/ {{ number_format($pricing->price, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h5 class="font-semibold text-yellow-800 mb-2">📋 Información Importante:</h5>
        <ul class="text-sm text-yellow-700 space-y-1">
            <li>• Las tarifas de 1, 2 y 3 clases incluyen un recargo del {{ $record->pricing_surcharge_percentage }}%</li>
            <li>• La tarifa de 4 clases es el precio estándar mensual (sin recargo)</li>
            <li>• Para instructores voluntarios: la 5ta clase tiene 25% adicional sobre la tarifa mensual</li>
            <li>• Para instructores no voluntarios: la 5ta clase tiene el mismo precio que 4 clases</li>
            <li>• Estas tarifas se actualizan automáticamente al cambiar la tarifa mensual o el porcentaje de recargo</li>
        </ul>
    </div>
    
</div>