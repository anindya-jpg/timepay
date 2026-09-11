<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Nama tabel di database.
     */
    protected $table = 'admin';

    /**
     * Primary key custom (bukan 'id' default Laravel).
     */
    protected $primaryKey = 'id_admin';

    protected $fillable = [
        'nama',
        'username',
        'password',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
