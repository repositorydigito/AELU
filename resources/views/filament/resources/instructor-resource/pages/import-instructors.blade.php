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
                            NOMBRES, APELLIDOS, TIPO_DOCUMENTO, NRO_DOCUMENTO, FECHA_NACIMIENTO, NACIONALIDAD, CODIGO_PROFESOR, TIPO_PROFESOR, CELULAR, TELEFONO, DISTRITO, DIRECCION
                        </div>
                    </li>
                    <li>Valores permitidos para campos específicos:
                        <ul>
                            <li>TIPO_DOCUMENTO debe ser uno de estos valores:
                                <ul>
                                    <li>DNI</li>
                                    <li>Pasaporte</li>
                                    <li>Carné de Extranjería</li>
                                </ul>
                            </li>
                            <li>TIPO_PROFESOR debe ser uno de estos valores:
                                <ul>
                                    <li>Voluntario</li>
                                    <li>Por Horas</li>
                                </ul>
                            </li>
                            <li>FECHA_NACIMIENTO debe estar en formato YYYY-MM-DD (ejemplo: 1990-12-31)</li>
                        </ul>
                    </li>
                    <li>Campos opcionales: CELULAR, TELEFONO, DISTRITO, DIRECCION</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>