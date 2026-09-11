<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Karyawan extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'karyawan';

    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'nip',
        'nama',
        'jenis_kelamin',
        'alamat',
        'no_hp',
        'email',
        'no_rekening',
        'username',
        'password',
        'foto',
        'id_jabatan',
        'tanggal_masuk',
        'status_aktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['jabatan'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'tanggal_masuk' => 'date',
        ];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'id_karyawan', 'id_karyawan');
    }

    public function penggajians(): HasMany
    {
        return $this->hasMany(Penggajian::class, 'id_karyawan', 'id_karyawan');
    }
}