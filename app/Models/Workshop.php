<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'instructor_id',
        'start_date',
        'end_date',
        'weekday',
        'start_time',
        'end_time',
        'place',
        'max_students',
        'class_count',
        'monthly_fee',
        'final_monthly_fee',
        'surcharge_percentage',
        'icon',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime', 
    ];    

    /* public function instructorWorkshops(): HasMany
    {
        return $this->hasMany(InstructorWorkshop::class);
    } */    

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    public function treasuryTransactions(): HasMany
    {
        return $this->hasMany(Treasury::class);
    }
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }    
    public function classes(): HasMany
    {
        return $this->hasMany(WorkshopClass::class);
    }
    
    public function getWeekdayNumber(string $weekdayName): int
    {
        return match (ucfirst(strtolower($weekdayName))) {
            'Lunes' => Carbon::MONDAY,
            'Martes' => Carbon::TUESDAY,
            'Miércoles' => Carbon::WEDNESDAY,
            'Jueves' => Carbon::THURSDAY,
            'Viernes' => Carbon::FRIDAY,
            'Sábado' => Carbon::SATURDAY,
            'Domingo' => Carbon::SUNDAY,
            default => throw new \InvalidArgumentException("Nombre de día de la semana inválido: {$weekdayName}"),
        };
    }
    
    public function calculateClassDates(): array
    {
        if (!$this->start_date || !$this->weekday || !$this->class_count) {
            return [];
        }

        $classDates = [];
        $currentDate = Carbon::parse($this->start_date);
        $targetWeekday = $this->getWeekdayNumber($this->weekday);

        for ($i = 0; $i < $this->class_count; $i++) {
            // Si la fecha actual no es el día de la semana objetivo, avanzar
            while ($currentDate->dayOfWeek !== $targetWeekday) {
                $currentDate->addDay();
            }
            $classDates[] = $currentDate->copy(); // Añadir una copia para no modificar la original
            $currentDate->addWeek(); // Avanzar a la misma fecha en la siguiente semana
        }

        return $classDates;
    }

    protected static function booted()
    {
        static::saved(function ($workshop) {
            $workshop->generateWorkshopClasses();
        });
    }
   
    public function generateWorkshopClasses(): void
    {
        if (!$this->exists) { 
            return;
        }

        // Opcional: Eliminar clases existentes para regenerarlas si el horario ha cambiado
        $this->classes()->delete();

        $classDates = $this->calculateClassDates();

        foreach ($classDates as $classDate) {
            // Aquí podrías tener lógica para verificar si la fecha es un feriado,
            // si tienes una tabla de feriados global.
            $isHoliday = false; // Valor por defecto, reemplaza con tu lógica real de feriados

            $this->classes()->create([
                'class_date' => $classDate->toDateString(),
                'start_time' => $this->start_time->format('H:i:s'),
                'end_time' => $this->end_time->format('H:i:s'),
                'is_holiday' => $isHoliday,
                'notes' => $isHoliday ? 'Clase cancelada por feriado' : null,
            ]);
        }
    }

    public function enrolledCount()
    {
        return $this->enrollments()->count();
    }
}
