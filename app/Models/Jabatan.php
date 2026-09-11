<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan';

    protected $primaryKey = 'id_jabatan';

    protected $fillable = [
        'nama_jabatan',
        'gaji_pokok',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
        ];
    }

    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'id_jabatan', 'id_jabatan');
    }

    /**
     * Relasi many-to-many ke Tunjangan lewat tabel pivot jabatan_tunjangan.
     * Nominal tunjangan berbeda-beda per jabatan, makanya pakai withPivot.
     */
    public function tunjangans(): BelongsToMany
    {
        return $this->belongsToMany(
            Tunjangan::class,
            'jabatan_tunjangan',
            'id_jabatan',
            'id_tunjangan',
            'id_jabatan',
            'id_tunjangan'
        )
            ->using(JabatanTunjangan::class)
            ->withPivot(['id_jabatantunjangan', 'nominal']);
    }
}
