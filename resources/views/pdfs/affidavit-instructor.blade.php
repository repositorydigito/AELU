<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaración Jurada y Ficha Personal - Instructor</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Crucial para compatibilidad de caracteres en Dompdf */
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 15mm; /* Márgenes reducidos */
        }
        .header {
            text-align: center;
            margin-bottom: 20px; /* Reducido de 30px */
        }
        .header h1 {
            font-size: 22px; /* Reducido de 24px */
            margin-bottom: 3px; /* Reducido de 5px */
            color: #2c3e50;
        }
        .header h2 {
            font-size: 16px; /* Reducido de 18px */
            margin-top: 0;
            margin-bottom: 3px; /* Agregado para reducir espacio */
            color: #2c3e50;
        }
        .header h3 {
            font-size: 14px;
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px; /* Reducido de 20px */
            margin-bottom: 5px; /* Reducido de 8px */
            padding-bottom: 3px; /* Reducido de 5px */
            font-size: 12px; /* Reducido de 13px */
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
            margin-top: 8px; /* Reducido de 10px */
            font-size: 9px; /* Reducido de 10px */
        }
        table td {
            padding: 3px; /* Reducido de 5px */
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
            margin-top: 15px; /* Reducido significativamente */
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
            max-width: 120px; /* Reducido de 200px */
            height: auto;
            margin: 5px auto; /* Reducido de 10px */
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
        <div class="header" style="display: flex; align-items: center; justify-content: flex-start; text-align: left; margin-bottom: 15px;">
            <div style="flex: 0 0 auto; margin-right: 15px;">
                {!! $logoPath
                    ? '<img src="data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath)) . '" alt="Logo AELU" style="height: 40px;">'
                    : '<div style="height: 40px; width: 80px; border: 1px solid #ccc; text-align: center; line-height: 40px; font-size: 10px;">Logo AELU</div>'
                !!}
            </div>
            <div style="flex: 1; text-align: center;">
                <h2 style="margin: 0;">PROGRAMA ADULTO MAYOR AELU (PAMA)</h2>
                <h3 style="margin: 0;">Ficha Personal Del Profesor</h3>
            </div>
        </div>

        <div class="content">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                        <h2 style="margin-top: 0; margin-bottom: 10px;">Ficha Médica</h2>
                        <table style="width: 100%; margin-bottom: 10px;">
                             <tr>
                                <td style="width: 50%; padding: 4px; vertical-align: top;">
                                    <strong>Talla</strong><br>
                                    {{ $instructor->medicalRecord->height ?? 'N/A' }} m
                                </td>
                                <td style="width: 50%; padding: 4px; vertical-align: top;">
                                    <strong>Peso</strong><br>
                                    {{ $instructor->medicalRecord->weight ?? 'N/A' }} kg
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Género</strong><br>
                                    {{ $instructor->gender ?? 'N/A' }}
                                </td>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>¿Fuma?</strong><br>
                                    {{ $instructor->medicalRecord->smokes ? 'Sí' : 'No' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Nro. de cigarrillos al día</strong><br>
                                    {{ $instructor->medicalRecord->cigarettes_per_day ?? '0' }}
                                </td>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Seguro Médico</strong><br>
                                    {{ $instructor->medicalRecord->health_insurance ?? 'N/A' }}
                                </td>
                            </tr>
                        </table>

                       <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Condiciones médicas que padece</strong><br>
                                    {{ implode(', ', (array) ($instructor->medicalRecord->medical_conditions ?? [])) ?: 'Ninguna' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Alergias</strong><br>
                                    {{ implode(', ', (array) ($instructor->medicalRecord->allergies ?? [])) ?: 'Ninguna' }}
                                    {{ $instructor->medicalRecord->allergy_details ? '(' . $instructor->medicalRecord->allergy_details . ')' : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Operaciones a las que se ha sometido</strong><br>
                                    {{ implode(', ', (array) ($instructor->medicalRecord->surgical_operations ?? [])) ?: 'Ninguna' }}
                                    {{ $instructor->medicalRecord->surgical_operation_details ? '(' . $instructor->medicalRecord->surgical_operation_details . ')' : '' }}
                                </td>
                            </tr>
                        </table>

                        <p style="margin: 8px 0 5px 0;"><strong>Medicamentos que toma:</strong></p>
                        @if ($instructor->medicalRecord && $instructor->medicalRecord->medications->isNotEmpty())
                            <table style="width: 100%; border: 1px solid #ccc; border-collapse: collapse; font-size: 8px;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #ccc; padding: 3px;">Medicina</th>
                                        <th style="border: 1px solid #ccc; padding: 3px;">Dosis</th>
                                        <th style="border: 1px solid #ccc; padding: 3px;">Horario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instructor->medicalRecord->medications as $medication)
                                        <tr>
                                            <td style="border: 1px solid #ccc; padding: 3px;">{{ $medication->medicine }}</td>
                                            <td style="border: 1px solid #ccc; padding: 3px;">{{ $medication->dose }}</td>
                                            <td style="border: 1px solid #ccc; padding: 3px;">{{ $medication->schedule }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p style="margin: 5px 0;">Ninguno</p>
                        @endif

                        <!-- Contacto de emergencia movido aquí -->
                        <h3 style="margin: 15px 0 5px 0;">Contacto de emergencia</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Familiar responsable</strong><br>
                                    {{ $instructor->emergency_contact_name ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Parentesco o relación</strong><br>
                                    {{ $instructor->emergency_contact_relationship ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; vertical-align: top;">
                                    <strong>Teléfono del familiar</strong><br>
                                    {{ $instructor->emergency_contact_phone ?? 'N/A' }}
                                </td>
                            </tr>
                        </table>                        
                    </td>

                    <td style="width: 40%; vertical-align: top; text-align: center; padding-left: 20px;">
                        @php
                            $photoPath = $instructor->photo ? storage_path('app/public/' . $instructor->photo) : null;
                            $photoPath = $photoPath && file_exists($photoPath) ? $photoPath : null;
                            $photoMime = $photoPath ? (in_array(strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png') : null;
                        @endphp
                        {!! $photoPath ? '<img src="data:'.$photoMime.';base64,'.base64_encode(file_get_contents($photoPath)).'" alt="Foto del usuario" style="width: 120px; height: auto; border-radius: 8px; margin-bottom: 8px;">' : '<div style="width: 120px; height: 120px; border: 2px solid #ccc; display: inline-block; text-align: center; line-height: 120px; font-size: 12px; margin-bottom: 8px;">Foto del usuario</div>' !!}
                        <h2 style="margin: 8px 0;">Datos personales</h2>
                        <div style="text-align: left; font-size: 9px;">
                            <p style="margin: 3px 0;"><strong>Apellidos:</strong> {{ $instructor->last_names }}</p>
                            <p style="margin: 3px 0;"><strong>Nombres:</strong> {{ $instructor->first_names }}</p>
                            <p style="margin: 3px 0;"><strong>Nacionalidad:</strong> {{ $instructor->nationality ?? 'N/A' }}</p>
                            <p style="margin: 3px 0;"><strong>Fecha de nacimiento:</strong> {{ $instructor->birth_date ? $instructor->birth_date->format('d/m/Y') : 'N/A' }}</p>
                            <p style="margin: 3px 0;"><strong>DNI:</strong> {{ $instructor->document_number }}</p>
                            <p style="margin: 3px 0;"><strong>Categoría de socio:</strong> {{ $instructor->category_partner ?? 'N/A' }}</p>
                            <p style="margin: 3px 0;"><strong>Celular:</strong> {{ $instructor->cell_phone ?? 'N/A' }}</p>
                            <p style="margin: 3px 0;"><strong>Teléfono de casa:</strong> {{ $instructor->home_phone ?? 'N/A' }}</p>
                            <p style="margin: 3px 0;"><strong>Dirección:</strong> {{ $instructor->address ?? 'No especificado' }}</p>
                        </div>

                        
                    </td>
                </tr>
            </table>
        </div>
        <!-- Firma movida aquí para aprovechar mejor el espacio -->
        <div class="signature-area" style="margin-top: 25px;">
            @if ($instructor->affidavit && $instructor->affidavit->digital_signature_and_fingerprint_path)
                <img src="{{ asset('storage/' . $instructor->affidavit->digital_signature_and_fingerprint_path) }}" alt="Firma y Huella Digital" class="signature-image">
            @else
                <div style="height: 50px; border: 1px solid #ccc; display: inline-block; text-align: center; line-height: 50px; font-size: 10px; width: 150px; margin: 8px auto;">
                    Firma y Huella Digital
                </div>
            @endif
            <p style="font-size: 9px; font-weight: bold; margin: 5px 0 2px 0;">FIRMA Y HUELLA DIGITAL</p>
            <p style="font-size: 9px; font-weight: bold; margin: 0;">DEL PROFESOR</p>
            <p style="font-size: 8px; margin: 3px 0;">DNI: <strong>{{ $instructor->document_number }}</strong></p>
        </div>
    </div>

    <!-- Salto de página para que la declaración jurada empiece en una nueva hoja -->
    <div style="page-break-before: always;"></div>

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
                <p>Yo, <strong>{{ $instructor->first_names }} {{ $instructor->last_names }}</strong>, identificado/a con <strong>{{ $instructor->document_type }} </strong> <strong>{{ $instructor->document_number }}</strong>, de nacionalidad <strong>{{ $instructor->nationality }}</strong>, con fecha de nacimiento <strong>{{ $instructor->birth_date ? $instructor->birth_date->format('d/m/Y') : 'N/A' }}</strong>, con domicilio en <strong>{{ $instructor->address ?? 'No especificado' }}</strong>, en pleno uso de mis facultades mentales, declaro bajo juramento lo siguiente:</p>
            </div>

            <div class="text-block">
                <p>1. Que, los datos consignados en la "Ficha Personal Del Profesor" (La Cual Se Encuentra anexada a Esta Página), se ajustan a la realidad.</p>
                <p>2. Que, gozo de buena salud física y mental para impartir talleres en el PROGRAMA ADULTO MAYOR AELU (PAMA).</p>
                <p>3. Que, conozco y acepto realizar las actividades de instrucción del referido programa.</p>
                <p>4. Que, me comprometo a cumplir con las responsabilidades asignadas como profesor de la ASOCIACIÓN ESTADIO LA UNIÓN - AELU.</p>
                <p>5. Que, exonero de toda responsabilidad a la ASOCIACIÓN ESTADIO LA UNIÓN - AELU, de cualquier hecho que pudiera afectar mi salud por motivo de la participación en dichos talleres como instructor.</p>
            </div>

            <div class="date-location">
                <p>Pueblo Libre, {{ \Carbon\Carbon::now()->day }} de {{ \Carbon\Carbon::now()->translatedFormat('F') }} del {{ \Carbon\Carbon::now()->year }}</p>
            </div>

            <div class="signature-area">                
                @if ($instructor->affidavit && $instructor->affidavit->digital_signature_and_fingerprint_path)
                    <img src="{{ asset('storage/' . $instructor->affidavit->digital_signature_and_fingerprint_path) }}" alt="Firma y Huella Digital" class="signature-image">
                @else
                    <div style="height: 80px; border: 1px solid #ccc; display: inline-block; text-align: center; line-height: 80px; font-size: 12px; width: 200px; margin: 10px auto;">
                        Firma y Huella Digital
                    </div>
                @endif
                <p class="section-title">FIRMA Y HUELLA DIGITAL DEL DECLARANTE</p>
                <p style="font-size: 10px; margin-top: 5px;">DNI: <strong>{{ $instructor->document_number }}</strong></p>
            </div>
        </div>
    </div>
</body>
</html>