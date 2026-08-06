<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'receber_aviso_email',
        'receber_aviso_whatsapp',
        'telefone_whatsapp',
        'whatsapp_consentimento_em',
        'preferencias_notificacao_definidas_em',
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
            'receber_aviso_email' => 'boolean',
            'receber_aviso_whatsapp' => 'boolean',
            'whatsapp_consentimento_em' => 'datetime',
            'preferencias_notificacao_definidas_em' => 'datetime',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_primary_admin' => 'boolean',
        ];
    }
}
