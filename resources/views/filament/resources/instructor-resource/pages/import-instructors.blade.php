<x-filament::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div class="flex justify-start mt-6">
            <x-filament::button type="submit">
                Importar Profesores
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-6">
        <div class="space-y-4">
            <h3 class="text-lg font-medium">
                Instrucciones para la importación
            </h3>
            <div class="prose">
                <ul>
                    <li>El archivo debe estar en formato Excel (.xlsx)</li>
                    <li>Las columnas deben tener los siguientes encabezados <strong>exactamente como se muestran</strong>:
                        <div class="bg-gray-50 p-4 my-2 font-mono text-sm">
                            nombres, apellidos, tipo_documento, nro_documento, fecha_nacimiento, nacionalidad, codigo_profesor, tipo_profesor, celular, telefono, distrito, direccion
                        </div>
                    </li>
                    <li>Valores permitidos para campos específicos:
                        <ul>
                            <li>tipo_documento debe ser uno de estos valores:
                                <ul>
                                    <li>DNI</li>
                                    <li>PASAPORTE</li>
                                    <li>CARNÉ DE EXTRANJERÍA</li>
                                </ul>
                            </li>
                            <li>tipo_profesor debe ser uno de estos valores:
                                <ul>
                                    <li>VOLUNTARIO</li>
                                    <li>POR HORAS</li>
                                </ul>
                            </li>
                            <li>fecha_nacimiento debe estar en formato YYYY-MM-DD (ejemplo: 1990-12-31)</li>
                        </ul>
                    </li>
                    <li>Campos opcionales: celular, telefono, distrito, direccion</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>