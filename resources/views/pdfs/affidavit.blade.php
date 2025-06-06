<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Declaración Jurada y Ficha Personal</title>
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
            {!! $logoPath ? '<img src="data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath)).'" alt="Logo AELU" style="height: 50px; margin-bottom: 1rem;">' : '<div style="height: 50px; width: 100px; border: 1px solid #ccc; display: inline-block; text-align: center; line-height: 50px; font-size: 12px;">Logo AELU</div>' !!}
            <h2>PROGRAMA ADULTO MAYOR AELU (PAMA)</h2>
            <h3>Ficha Personal Del Asociado Participante</h3>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: top; padding-right: 20px;">
                    <h2>Ficha Médica</h2>
                    <table style="width: 100%; margin-bottom: 15px;">
                         <tr>
                            <td style="width: 50%; padding: 8px; vertical-align: top;">
                                <strong>Talla</strong><br>
                                {{ $student->medicalRecord->height ?? 'N/A' }} m
                            </td>
                            <td style="width: 50%; padding: 8px; vertical-align: top;">
                                <strong>Peso</strong><br>
                                {{ $student->medicalRecord->weight ?? 'N/A' }} kg
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Género</strong><br>
                                {{ $student->gender ?? 'N/A' }}
                            </td>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>¿Fuma?</strong><br>
                                {{ $student->medicalRecord->smokes ? 'Sí' : 'No' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Nro. de cigarrillos al día</strong><br>
                                {{ $student->medicalRecord->cigarettes_per_day ?? '0' }}
                            </td>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Seguro Médico</strong><br>
                                {{ $student->medicalRecord->health_insurance ?? 'N/A' }}
                            </td>
                        </tr>
                    </table>

                   <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Condiciones médicas que padece</strong><br>
                                {{ implode(', ', (array) ($student->medicalRecord->medical_conditions ?? [])) ?: 'Ninguna' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Alergias</strong><br>
                                {{ implode(', ', (array) ($student->medicalRecord->allergies ?? [])) ?: 'Ninguna' }}
                                {{ $student->medicalRecord->allergy_details ? '(' . $student->medicalRecord->allergy_details . ')' : '' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; vertical-align: top;">
                                <strong>Operaciones a las que se ha sometido</strong><br>
                                {{ implode(', ', (array) ($student->medicalRecord->surgical_operations ?? [])) ?: 'Ninguna' }}
                                {{ $student->medicalRecord->surgical_operation_details ? '(' . $student->medicalRecord->surgical_operation_details . ')' : '' }}
                            </td>
                        </tr>
                    </table>

                    <p><strong>Medicamentos que toma:</strong></p>
                    @if ($student->medicalRecord && $student->medicalRecord->medications->isNotEmpty())
                        <table style="width: 100%; border: 1px solid #ccc; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ccc; padding: 5px;">Medicina</th>
                                    <th style="border: 1px solid #ccc; padding: 5px;">Dosis</th>
                                    <th style="border: 1px solid #ccc; padding: 5px;">Horario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->medicalRecord->medications as $medication)
                                    <tr>
                                        <td style="border: 1px solid #ccc; padding: 5px;">{{ $medication->medicine }}</td>
                                        <td style="border: 1px solid #ccc; padding: 5px;">{{ $medication->dose }}</td>
                                        <td style="border: 1px solid #ccc; padding: 5px;">{{ $medication->schedule }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Ninguno</p>
                    @endif
                </td>

                <td style="width: 40%; vertical-align: top; text-align: center; padding-left: 20px;">
                    @php
                        $photoPath = $student->photo ? storage_path('app/public/' . $student->photo) : null;
                        $photoPath = $photoPath && file_exists($photoPath) ? $photoPath : null;
                        $photoMime = $photoPath ? (in_array(strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png') : null;
                    @endphp
                    {!! $photoPath ? '<img src="data:'.$photoMime.';base64,'.base64_encode(file_get_contents($photoPath)).'" alt="Foto del usuario" style="width: 150px; height: auto; border-radius: 8px; margin-bottom: 10px;">' : '<div style="width: 150px; height: 150px; border: 2px solid #ccc; display: inline-block; text-align: center; line-height: 150px; font-size: 14px; margin-bottom: 10px;">Foto del usuario</div>' !!}
                    <h2>Datos personales</h2>
                    <div style="text-align: left;">
                        <p><strong>Apellidos:</strong> {{ $student->last_names }}</p>
                        <p><strong>Nombres:</strong> {{ $student->first_names }}</p>
                        <p><strong>Nacionalidad:</strong> {{ $student->nationality ?? 'N/A' }}</p>
                        <p><strong>Fecha de nacimiento:</strong> {{ $student->birth_date->format('d/m/Y') }}</p>
                        <p><strong>DNI:</strong> {{ $student->document_number }}</p>
                        <p><strong>Código de asociado:</strong> {{ $student->student_code ?? 'N/A' }}</p>
                        <p><strong>Categoría de socio:</strong> {{ $student->category_partner ?? 'N/A' }}</p>
                        <p><strong>Celular:</strong> {{ $student->cell_phone ?? 'N/A' }}</p>
                        <p><strong>Teléfono de casa:</strong> {{ $student->home_phone ?? 'N/A' }}</p>
                        <p><strong>Dirección:</strong> {{ $student->address ?? 'No especificado' }}</p>
                    </div>
                </td>
            </tr>
        </table>
        <h3>Contacto de emergencia</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%; padding: 8px; vertical-align: top;">
                    <strong>Familiar responsable</strong><br>
                    {{ $student->emergency_contact_name ?? 'N/A' }}
                </td>
                <td style="width: 33.33%; padding: 8px; vertical-align: top;">
                    <strong>Parentesco o relación</strong><br>
                    {{ $student->emergency_contact_relationship ?? 'N/A' }}
                </td>
                <td style="width: 33.33%; padding: 8px; vertical-align: top;">
                    <strong>Teléfono del familiar</strong><br>
                    {{ $student->emergency_contact_phone ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

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
                <p>Yo, <strong>{{ $student->first_names }} {{ $student->last_names }}</strong>, identificado/a con <strong>{{ $student->document_type }} </strong> <strong>{{ $student->document_number }}</strong>, de nacionalidad <strong>{{ $student->nationality }}</strong>, con fecha de nacimiento <strong>{{ $student->birth_date->format('d/m/Y') }}</strong>, con domicilio en <strong>{{ $student->address ?? 'No especificado' }}</strong> y código de asociado <strong>{{ $student->student_code ?? 'N/A' }}</strong>, en pleno uso de mis facultades mentales, declaro bajo juramento lo siguiente:</p>
            </div>

            <div class="text-block">
                <p>1. Que, los datos consignados en la “Ficha Personal Del Asociado Participante” (La Cual Se Encuentra anexada a Esta Página), se ajustan a la realidad.</p>
                <p>2. Que, gozo de buena salud física y mental para participar en los talleres del PROGRAMA ADULTO MAYOR AELU (PAMA).</p>
                <p>3. Que, conozco y acepto realizar las actividades del referido programa por haberlo consultado con mi médico.</p>
                <p>4. Que, exonero de toda responsabilidad a la ASOCIACIÓN ESTADIO LA UNIÓN - AELU, de cualquier hecho que pudiera afectar mi salud por motivo de la participación en dichos talleres.</p>
            </div>

            <div class="date-location">
                <p>Pueblo Libre, {{ \Carbon\Carbon::now()->day }} de {{ \Carbon\Carbon::now()->translatedFormat('F') }} del {{ \Carbon\Carbon::now()->year }}</p>
            </div>

            <div class="signature-area">                
                @if ($student->affidavit && $student->affidavit->digital_signature_and_fingerprint_path)
                    <img src="{{ asset('storage/' . $student->affidavit->digital_signature_and_fingerprint_path) }}" alt="Firma y Huella Digital" class="signature-image">
                @else
                    <p>Firma y Huella Digital no adjuntas.</p>
                @endif
                <p class="section-title">FIRMA Y HUELLA DIGITAL DEL DECLARANTE</p>
                <!-- <div class="signature-line"></div>
                <p class="signature-text">
                    DNI: <strong>{{ $student->document_number }}</strong>
                </p> -->
            </div>
        </div>
    </div>
</body>
</html>