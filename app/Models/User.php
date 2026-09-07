<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_locked',
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
            'is_locked' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    public function asesor(): HasOne
    {
        return $this->hasOne(Asesor::class);
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class);
    }

    /**
     * Prodi managed by this user when acting as admin_prodi.
     */
    public function prodiDikelola(): BelongsToMany
    {
        return $this->belongsToMany(Prodi::class, 'admin_prodi_prodi');
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->hasRole('super_admin') => 'super-admin.dashboard',
            $this->hasRole('admin_prodi') => 'admin-prodi.dashboard',
            $this->hasRole('asesor') => 'asesor.dashboard',
            $this->hasRole('mahasiswa') => 'mahasiswa.dashboard',
            default => 'login',
        };
    }
}
