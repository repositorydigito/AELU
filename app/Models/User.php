<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'enrollment_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function paymentRegisteredBatches()
    {
        return $this->hasMany(EnrollmentBatch::class, 'payment_registered_by_user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Verificar si el usuario NO tiene el rol Delegado
            // Usamos 'created' en lugar de 'creating' porque necesitamos que el usuario 
            // ya exista en la BD para poder verificar sus roles
            if (!$user->hasRole('Delegado') && empty($user->enrollment_code)) {
                // Obtener el último código asignado
                $lastUser = static::whereNotNull('enrollment_code')
                    ->orderBy('enrollment_code', 'desc')
                    ->first();
                
                if ($lastUser && $lastUser->enrollment_code) {
                    $nextCode = str_pad((int)$lastUser->enrollment_code + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $nextCode = '001';
                }
                
                $user->enrollment_code = $nextCode;
                $user->saveQuietly();
            }
        });
    }
}
