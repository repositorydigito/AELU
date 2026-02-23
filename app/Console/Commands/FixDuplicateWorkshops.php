<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateWorkshops extends Command
{
    protected $signature = 'workshops:fix-duplicates {--dry-run : Mostrar qué se haría sin ejecutar cambios}';

    protected $description = 'Consolida inscripciones de talleres duplicados y elimina los duplicados del período marzo 2026';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 MODO DRY-RUN: Solo mostrando cambios, sin ejecutar');
        } else {
            $this->warn('⚠️  ATENCIÓN: Este comando modificará la base de datos');
            if (!$this->confirm('¿Tienes un backup y deseas continuar?')) {
                $this->info('Operación cancelada');
                return Command::SUCCESS;
            }
        }

        // Lista de talleres duplicados (ID_KEEPER => ID_DUPLICATE)
        $duplicates = [
            425 => 426,  // ACTIVIDAD FISICA - Jueves 11:00
            408 => 409,  // ACTIVIDAD FISICA - Martes 11:00
            450 => 451,  // ACTIVIDAD FISICA - Sábado 11:00
            400 => 463,  // GIMNASIA AERÓBICA - Jueves 12:00
            395 => 462,  // GIMNASIA AERÓBICA - Martes 12:00
            388 => 439,  // TAI CHI - Lunes 11:00
            438 => 440,  // TAI CHI - Viernes 11:00
        ];

        $this->info("\n📋 Procesando " . count($duplicates) . " pares de talleres duplicados...\n");

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($duplicates as $keeperId => $duplicateId) {
                // Obtener info de los talleres
                $keeper = DB::table('workshops')->where('id', $keeperId)->first();
                $duplicate = DB::table('workshops')->where('id', $duplicateId)->first();

                if (!$keeper || !$duplicate) {
                    $this->error("  ❌ No se encontró taller {$keeperId} o {$duplicateId}");
                    continue;
                }

                $this->line("📌 {$keeper->name} - {$keeper->day_of_week} {$keeper->start_time}");
                $this->line("   Keeper: ID {$keeperId} | Duplicate: ID {$duplicateId}");

                // 1. Obtener los instructor_workshops
                $keeperIW = DB::table('instructor_workshops')
                    ->where('workshop_id', $keeperId)
                    ->first();

                $duplicateIW = DB::table('instructor_workshops')
                    ->where('workshop_id', $duplicateId)
                    ->first();

                if (!$keeperIW || !$duplicateIW) {
                    $this->error("  ❌ Falta instructor_workshop");
                    continue;
                }

                // 2. Contar inscripciones a migrar
                $enrollmentsCount = DB::table('student_enrollments')
                    ->where('instructor_workshop_id', $duplicateIW->id)
                    ->count();

                $this->line("   → Migrar {$enrollmentsCount} inscripciones");

                if (!$isDryRun) {
                    // 3. Migrar inscripciones
                    DB::table('student_enrollments')
                        ->where('instructor_workshop_id', $duplicateIW->id)
                        ->update(['instructor_workshop_id' => $keeperIW->id]);

                    // 4. Eliminar instructor_workshop duplicado
                    DB::table('instructor_workshops')
                        ->where('id', $duplicateIW->id)
                        ->delete();

                    // 5. Eliminar clases del taller duplicado
                    $deletedClasses = DB::table('workshop_classes')
                        ->where('workshop_id', $duplicateId)
                        ->delete();

                    // 6. Eliminar taller duplicado
                    DB::table('workshops')
                        ->where('id', $duplicateId)
                        ->delete();

                    $this->info("   ✅ Completado ({$deletedClasses} clases eliminadas)");
                } else {
                    $classesCount = DB::table('workshop_classes')
                        ->where('workshop_id', $duplicateId)
                        ->count();
                    $this->line("   → Eliminar {$classesCount} clases del taller duplicado");
                    $this->line("   → Eliminar instructor_workshop duplicado");
                    $this->line("   → Eliminar taller duplicado ID {$duplicateId}");
                }

                $this->newLine();
            }

            // Marcar período como replicado
            if (!$isDryRun) {
                DB::table('monthly_periods')
                    ->where('year', 2026)
                    ->where('month', 3)
                    ->update(['workshops_replicated_at' => '2026-02-19 18:59:02']);

                DB::commit();

                $this->info('✅ Consolidación completada exitosamente');
                $this->info('✅ Período marzo 2026 marcado como replicado');
            } else {
                $this->line("→ Marcar período marzo 2026 como replicado");
                $this->newLine();
                $this->info('✅ Vista previa completada. Ejecuta sin --dry-run para aplicar cambios.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
