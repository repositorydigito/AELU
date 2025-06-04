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
            border-bottom: 1px solid #ccc;
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
            margin-bottom: 15px;
            text-align: justify; /* Texto justificado para los párrafos de la declaración */
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
        <div class="header">
            <h1>FICHA PERSONAL DEL ALUMNO(A)</h1>
        </div>

        <div class="content">
            <div class="section-title">DATOS DEL ALUMNO(A)</div>
            <div class="data-item"><strong>Apellidos y Nombres:</strong> {{ $student->last_names }} {{ $student->first_names }}</div>
            <div class="data-item"><strong>Tipo y N° Documento:</strong> {{ $student->document_type ?? 'N/A' }} - {{ $student->document_number }}</div>
            <div class="data-item"><strong>Fecha de Nacimiento:</strong> {{ $student->birth_date->format('d/m/Y') }}</div>
            <div class="data-item"><strong>Nacionalidad:</strong> {{ $student->nationality ?? 'N/A' }}</div>
            <div class="data-item"><strong>Código de Alumno:</strong> {{ $student->student_code ?? 'N/A' }}</div>
            <div class="data-item"><strong>Categoría Socio:</strong> {{ $student->category_partner ?? 'N/A' }}</div>
            <div class="data-item"><strong>Teléfono Celular:</strong> {{ $student->cell_phone ?? 'N/A' }}</div>
            <div class="data-item"><strong>Teléfono Fijo:</strong> {{ $student->home_phone ?? 'N/A' }}</div>
            <div class="data-item"><strong>Distrito:</strong> {{ $student->district ?? 'N/A' }}</div>
            <div class="data-item"><strong>Dirección:</strong> {{ $student->address ?? 'No especificado' }}</div>

            <div class="section-title">FICHA MÉDICA RESUMEN</div>
            <table>
                <tr class="no-border">
                    <td><strong>Peso:</strong> {{ $student->medicalRecord->weight ?? 'N/A' }} kg</td>
                    <td><strong>Talla:</strong> {{ $student->medicalRecord->height ?? 'N/A' }} m</td>
                    {{-- ESTE CAMPO NO ESTÁ EN TU MODELO MedicalRecord. Si lo necesitas, debes añadirlo. --}}
                    <td><strong>Grupo Sanguíneo:</strong> PENDIENTE: Dato</td>
                </tr>
                <tr class="no-border">
                    <td colspan="3"><strong>Condiciones Médicas:</strong> {{ implode(', ', (array) ($student->medicalRecord->medical_conditions ?? [])) ?: 'Ninguna' }}</td>
                </tr>
                <tr class="no-border">
                    <td colspan="3"><strong>Alergias:</strong> {{ implode(', ', (array) ($student->medicalRecord->allergies ?? [])) ?: 'Ninguna' }} {{ $student->medicalRecord->allergy_details ? '(' . $student->medicalRecord->allergy_details . ')' : '' }}</td>
                </tr>
                <tr class="no-border">
                    <td colspan="3"><strong>Operaciones Sometido:</strong> {{ implode(', ', (array) ($student->medicalRecord->surgical_operations ?? [])) ?: 'Ninguna' }} {{ $student->medicalRecord->surgical_operation_details ? '(' . $student->medicalRecord->surgical_operation_details . ')' : '' }}</td>
                </tr>
                <tr class="no-border">
                    <td colspan="3">
                        <strong>Medicamentos que toma:</strong>
                        @if ($student->medicalRecord && $student->medicalRecord->medications->isNotEmpty())
                            <ul>
                                @foreach ($student->medicalRecord->medications as $medication)
                                    <li>{{ $medication->medicine }} @if($medication->dose) ({{ $medication->dose }}) @endif @if($medication->schedule) - {{ $medication->schedule }} @endif</li>
                                @endforeach
                            </ul>
                        @else
                            Ninguno
                        @endif
                    </td>
                </tr>
                <tr class="no-border">
                    {{-- ESTE CAMPO NO ESTÁ EN TU MODELO MedicalRecord. Si lo necesitas, debes añadirlo. --}}
                    <td colspan="3"><strong>Observaciones:</strong> PENDIENTE: Dato</td>
                </tr>
            </table>
        </div>

        <div class="section-title">DATOS DEL FAMILIAR RESPONSABLE</div>
        <table>
            <tr class="no-border">
                <td><strong>Nombres y Apellidos:</strong> {{ $student->emergency_contact_name ?? 'N/A' }}</td>
                {{-- Tu modelo Student no tiene un campo para el DNI del contacto de emergencia --}}
                <td><strong>DNI:</strong> PENDIENTE: DNI Familiar</td>
            </tr>
            <tr class="no-border">
                <td><strong>Parentesco:</strong> {{ $student->emergency_contact_relationship ?? 'N/A' }}</td>
                <td><strong>Teléfono:</strong> {{ $student->emergency_contact_phone ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="page">
        <div class="header">
            <h1>DECLARACIÓN JURADA</h1>
            <h2>DEL ALUMNO(A)</h2>
        </div>

        <div class="content">
            <div class="text-block">
                <p>Yo, <strong>{{ $student->first_names }} {{ $student->last_names }}</strong>, con DNI <strong>{{ $student->document_number }}</strong>, de nacionalidad <strong>{{ $student->nationality }}</strong>, con fecha de nacimiento <strong>{{ $student->birth_date->format('d/m/Y') }}</strong>, con domicilio en <strong>{{ $student->address ?? 'No especificado' }}</strong>, en pleno uso de mis facultades mentales, declaro bajo juramento lo siguiente:</p>
            </div>

            <div class="text-block">
                <p>1. Que la información brindada en este documento es verdadera y completa.</p>
                <p>2. Que me encuentro en óptimas condiciones de salud para realizar las actividades.</p>
                <p>3. Que autorizo la atención médica en caso de emergencia y asumo los gastos que de ello se deriven.</p>
            </div>

            <div class="date-location">
                <p>LIMA, {{ \Carbon\Carbon::now()->day }} de {{ \Carbon\Carbon::now()->translatedFormat('F') }} del {{ \Carbon\Carbon::now()->year }}</p>
            </div>

            <div class="signature-area">
                <p class="section-title">FIRMA Y HUELLA DIGITAL DEL DECLARANTE</p>
                @if ($student->affidavit && $student->affidavit->digital_signature_and_fingerprint_path)
                    {{-- La ruta debe ser accesible por Dompdf. Asegúrate de 'php artisan storage:link' --}}
                    <img src="{{ asset('storage/' . $student->affidavit->digital_signature_and_fingerprint_path) }}" alt="Firma y Huella Digital" class="signature-image">
                @else
                    <p>Firma y Huella Digital no adjuntas.</p>
                @endif
                <div class="signature-line"></div>
                <p class="signature-text">
                    DNI: <strong>{{ $student->document_number }}</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>