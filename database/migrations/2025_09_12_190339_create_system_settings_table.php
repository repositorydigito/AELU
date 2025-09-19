<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, float, json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key');
        });

        // Insertar configuraciones por defecto
        \App\Models\SystemSetting::create([
            'key' => 'auto_cancel_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Habilitar/deshabilitar anulación automática de inscripciones pendientes'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_cancel_day',
            'value' => '28',
            'type' => 'integer',
            'description' => 'Día del mes para anular inscripciones pendientes'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_cancel_time',
            'value' => '23:59:59',
            'type' => 'string',
            'description' => 'Hora del día para ejecutar anulación automática'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_cancel_grace_hours',
            'value' => '0',
            'type' => 'integer',
            'description' => 'Horas de gracia después del día límite'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_generate_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Habilitar/deshabilitar generación automática de inscripciones para el mes siguiente'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_generate_day',
            'value' => '20',
            'type' => 'integer',
            'description' => 'Día del mes para generar inscripciones del mes siguiente'
        ]);

        \App\Models\SystemSetting::create([
            'key' => 'auto_generate_time',
            'value' => '23:59:59',
            'type' => 'string',
            'description' => 'Hora del día para ejecutar generación automática'
        ]);
    }
    
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
