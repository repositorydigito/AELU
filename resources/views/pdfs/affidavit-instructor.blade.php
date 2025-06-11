<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaración Jurada y Ficha Personal - Instructor</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Crucial para compatibilidad de caracteres en Dompdf */
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            margin: 20mm; /* Márgenes generales */
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .header h2 {
            font-size: 18px;
            margin-top: 0;
            color: #2c3e50;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            padding-bottom: 5px;
            font-size: 13px;
        }
        .data-item {
            margin-bottom: 5px;
        }
        .data-item strong {
            display: inline-block;
            width: 150px; /* Ancho fijo para las etiquetas */
            vertical-align: top;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        table td {
            padding: 5px;
            border: 1px solid #eee; /* Borde suave para las celdas */
            vertical-align: top;
        }
        .no-border td {
            border: none;
        }
        ul {
            margin: 0;
            padding-left: 20px;
            list-style-type: disc;
        }
        ul li {
            margin-bottom: 3px;
        }
        .text-block {
            margin-top: 10px;
            text-align: justify;
        }
        .date-location {
            text-align: right;
            margin-top: 30px;
            font-size: 11px;
        }
        .signature-area {
            text-align: center;
            margin-top: 60px;
            page-break-inside: avoid; /* Evita que esta sección se corte entre páginas */
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin: 0 auto;
            margin-top: 50px; /* Espacio para la firma */
        }
        .signature-text {
            margin-top: 5px;
            font-size: 10px;
        }
        .signature-image {
            max-width: 200px; /* Ajusta el tamaño de la imagen */
            height: auto;
            margin: 10px auto;
            display: block; /* Centrar la imagen */
        }

        /* Clase para forzar un salto de página */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="page">
        @php
            $logoFiles = ['images/logoAelu.png', 'images/logoAELU.png', 'images/logoAELU.svg'];
            $logoPath = collect($logoFiles)->map(fn($file) => public_path($file))->first(fn($path) => file_exists($path));
            $logoMime = $logoPath ? (str_ends_with($logoPath, '.svg') ? 'image/svg+xml' : 'image/png') : null;
        @endphp
        <div class="header" style="display: flex; align-items: center; justify-content: flex-start; text-align: left;">
            <div style="flex: 0 0 auto; margin-right: 20px;">
                {!! $logoPath
                    ? '<img src="data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath)) . '" alt="Logo AELU" style="height: 50px;">'
                    : '<div style="height: 50px; width: 100px; border: 1px solid #ccc; text-align: center; line-height: 50px; font-size: 12px;">Logo AELU</div>'
                !!}
            </div>
            <div style="flex: 1; text-align: center;">
                <h1>FICHA PERSONAL DEL INSTRUCTOR</h1>
                <h2>ASOCIACIÓN ESTADIO LA UNIÓN - AELU</h2>
            </div>
        </div>

        <div class="content">
            <div class="section-title">DATOS PERSONALES</div>
            <table class="no-border">
                <tr>
                    <td style="width: 50%;">
                        <div class="data-item"><strong>Apellidos:</strong> {{ $instructor->last_names }}</div>
                        <div class="data-item"><strong>Nombres:</strong> {{ $instructor->first_names }}</div>
                        <div class="data-item"><strong>Tipo de Documento:</strong> {{ $instructor->document_type }}</div>
                        <div class="data-item"><strong>Número de Documento:</strong> {{ $instructor->document_number }}</div>
                        <div class="data-item"><strong>Fecha de Nacimiento:</strong> {{ $instructor->birth_date ? $instructor->birth_date->format('d/m/Y') : 'N/A' }}</div>
                        <div class="data-item"><strong>Nacionalidad:</strong> {{ $instructor->nationality }}</div>
                    </td>
                    <td style="width: 50%;">
                        <div class="data-item"><strong>Código de Instructor:</strong> {{ $instructor->instructor_code }}</div>
                        <div class="data-item"><strong>Tipo de Instructor:</strong> {{ $instructor->instructor_type }}</div>
                        <div class="data-item"><strong>Celular:</strong> {{ $instructor->cell_phone ?? 'N/A' }}</div>
                        <div class="data-item"><strong>Teléfono:</strong> {{ $instructor->home_phone ?? 'N/A' }}</div>
                        <div class="data-item"><strong>Distrito:</strong> {{ $instructor->district ?? 'N/A' }}</div>
                        <div class="data-item"><strong>Domicilio:</strong> {{ $instructor->address ?? 'N/A' }}</div>
                    </td>
                </tr>
            </table>

            @if($instructor->medicalRecord)
            <div class="section-title">FICHA MÉDICA</div>
            <table class="no-border">
                <tr>
                    <td style="width: 50%;">
                        <div class="data-item"><strong>Peso:</strong> {{ $instructor->medicalRecord->weight ? $instructor->medicalRecord->weight . ' kg' : 'N/A' }}</div>
                        <div class="data-item"><strong>Talla:</strong> {{ $instructor->medicalRecord->height ? $instructor->medicalRecord->height . ' cm' : 'N/A' }}</div>
                        <div class="data-item"><strong>Género:</strong> {{ $instructor->medicalRecord->gender ?? 'N/A' }}</div>
                        <div class="data-item"><strong>Fuma:</strong> {{ $instructor->medicalRecord->smokes ?? 'N/A' }}</div>
                        @if($instructor->medicalRecord->smokes === 'Sí')
                            <div class="data-item"><strong>Cigarrillos al día:</strong> {{ $instructor->medicalRecord->cigarettes_per_day ?? 'N/A' }}</div>
                        @endif
                        <div class="data-item"><strong>Seguro Médico:</strong> {{ $instructor->medicalRecord->health_insurance ?? 'N/A' }}</div>
                    </td>
                    <td style="width: 50%;">
                        <div class="data-item"><strong>Condiciones Médicas:</strong></div>
                        @if($instructor->medicalRecord->medical_conditions && is_array($instructor->medicalRecord->medical_conditions))
                            <ul>
                                @foreach($instructor->medicalRecord->medical_conditions as $condition)
                                    <li>{{ $condition }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div>Ninguna</div>
                        @endif

                        @if($instructor->medicalRecord->allergies && is_array($instructor->medicalRecord->allergies))
                            <div class="data-item"><strong>Alergias:</strong></div>
                            <ul>
                                @foreach($instructor->medicalRecord->allergies as $allergy)
                                    <li>{{ $allergy }}</li>
                                @endforeach
                            </ul>
                            @if($instructor->medicalRecord->allergy_details)
                                <div class="data-item"><strong>Detalle de Alergias:</strong> {{ $instructor->medicalRecord->allergy_details }}</div>
                            @endif
                        @endif

                        @if($instructor->medicalRecord->surgical_operations && is_array($instructor->medicalRecord->surgical_operations))
                            <div class="data-item"><strong>Operaciones:</strong></div>
                            <ul>
                                @foreach($instructor->medicalRecord->surgical_operations as $operation)
                                    <li>{{ $operation }}</li>
                                @endforeach
                            </ul>
                            @if($instructor->medicalRecord->surgical_operation_details)
                                <div class="data-item"><strong>Especificación:</strong> {{ $instructor->medicalRecord->surgical_operation_details }}</div>
                            @endif
                        @endif
                    </td>
                </tr>
            </table>

            @if($instructor->medicalRecord->medications && $instructor->medicalRecord->medications->count() > 0)
                <div class="section-title">MEDICAMENTOS</div>
                <table>
                    <tr>
                        <td style="font-weight: bold;">Medicina</td>
                        <td style="font-weight: bold;">Dosis</td>
                        <td style="font-weight: bold;">Horario</td>
                    </tr>
                    @foreach($instructor->medicalRecord->medications as $medication)
                        <tr>
                            <td>{{ $medication->medicine }}</td>
                            <td>{{ $medication->dose ?? 'N/A' }}</td>
                            <td>{{ $medication->schedule ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
            @endif

            @if($instructor->instructorWorkshops && $instructor->instructorWorkshops->count() > 0)
                <div class="section-title">TALLERES ASIGNADOS</div>
                <table>
                    <tr>
                        <td style="font-weight: bold;">Taller</td>
                        <td style="font-weight: bold;">Día</td>
                        <td style="font-weight: bold;">Horario</td>
                        <td style="font-weight: bold;">Lugar</td>
                        <td style="font-weight: bold;">Tarifa</td>
                    </tr>
                    @foreach($instructor->instructorWorkshops as $workshop)
                        <tr>
                            <td>{{ $workshop->workshop->name ?? 'N/A' }}</td>
                            <td>{{ $workshop->day_of_week ?? 'N/A' }}</td>
                            <td>{{ $workshop->start_time ? \Carbon\Carbon::parse($workshop->start_time)->format('H:i') : 'N/A' }} - {{ $workshop->end_time ? \Carbon\Carbon::parse($workshop->end_time)->format('H:i') : 'N/A' }}</td>
                            <td>{{ $workshop->place ?? 'N/A' }}</td>
                            <td>S/. {{ $workshop->class_rate ? number_format($workshop->class_rate, 2) : '0.00' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>

    <div class="page">
         @php
            $logoFiles = ['images/logoAelu.png', 'images/logoAELU.png', 'images/logoAELU.svg'];
            $logoPath = collect($logoFiles)->map(fn($file) => public_path($file))->first(fn($path) => file_exists($path));
            $logoMime = $logoPath ? (str_ends_with($logoPath, '.svg') ? 'image/svg+xml' : 'image/png') : null;
        @endphp
        <div class="header" style="position: relative; height: 60px; margin-bottom: 4rem;">
            <div style="position: absolute; left: 0; top: 50%; transform: translateY(-50%);">
                {!! $logoPath
                    ? '<img src="data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath)) . '" alt="Logo AELU" style="height: 50px;">'
                    : '<div style="height: 50px; width: 100px; border: 1px solid #ccc; text-align: center; line-height: 50px; font-size: 12px;">Logo AELU</div>'
                !!}
            </div>

            <h1 style="text-align: center; margin:4rem 0; position: absolute; left: 50%; transform: translateX(-50%); top: 50%; transform: translate(-50%, -50%); width: 100%;">
                DECLARACIÓN JURADA
            </h1>
        </div>

        <div class="content">
            <div class="text-block">
                <p>Yo, <strong>{{ $instructor->first_names }} {{ $instructor->last_names }}</strong>, identificado/a con <strong>{{ $instructor->document_type }} </strong> <strong>{{ $instructor->document_number }}</strong>, de nacionalidad <strong>{{ $instructor->nationality }}</strong>, con fecha de nacimiento <strong>{{ $instructor->birth_date ? $instructor->birth_date->format('d/m/Y') : 'N/A' }}</strong>, con domicilio en <strong>{{ $instructor->address ?? 'No especificado' }}</strong> y código de instructor <strong>{{ $instructor->instructor_code ?? 'N/A' }}</strong>, en pleno uso de mis facultades mentales, declaro bajo juramento lo siguiente:</p>
            </div>

            <div class="text-block">
                <p>1. Que, los datos consignados en la "Ficha Personal Del Instructor" (La Cual Se Encuentra anexada a Esta Página), se ajustan a la realidad.</p>
                <p>2. Que, gozo de buena salud física y mental para impartir talleres en el PROGRAMA ADULTO MAYOR AELU (PAMA).</p>
                <p>3. Que, conozco y acepto realizar las actividades de instrucción del referido programa.</p>
                <p>4. Que, me comprometo a cumplir con las responsabilidades asignadas como instructor de la ASOCIACIÓN ESTADIO LA UNIÓN - AELU.</p>
                <p>5. Que, exonero de toda responsabilidad a la ASOCIACIÓN ESTADIO LA UNIÓN - AELU, de cualquier hecho que pudiera afectar mi salud por motivo de la participación en dichos talleres como instructor.</p>
            </div>

            <div class="date-location">
                <p>Pueblo Libre, {{ \Carbon\Carbon::now()->day }} de {{ \Carbon\Carbon::now()->translatedFormat('F') }} del {{ \Carbon\Carbon::now()->year }}</p>
            </div>

            <div class="signature-area">                
                @if ($instructor->affidavit && $instructor->affidavit->digital_signature_and_fingerprint_path)
                    <img src="{{ asset('storage/' . $instructor->affidavit->digital_signature_and_fingerprint_path) }}" alt="Firma y Huella Digital" class="signature-image">
                @else
                    <p>Firma y Huella Digital no adjuntas.</p>
                @endif
                <p class="section-title">FIRMA Y HUELLA DIGITAL DEL DECLARANTE</p>
            </div>
        </div>
    </div>
</body>
</html>
