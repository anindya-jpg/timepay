<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penggajian extends Model
{
    use HasFactory;

    protected $table = 'penggajian';

    protected $primaryKey = 'id_gaji';

    protected $fillable = [
        'id_karyawan',
        'bulan',
        'tahun',
        'gaji_pokok',
        'total_tunjangan',
        'total_jam_lembur',
        'total_tunjangan_lembur',
        'total_potongan',
        'jumlah_hadir',
        'jumlah_telat',
        'jumlah_alpha',
        'gaji_bersih',
        'tanggal_generate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'total_tunjangan' => 'decimal:2',
            'total_jam_lembur' => 'decimal:2',
            'total_tunjangan_lembur' => 'decimal:2',
            'total_potongan' => 'decimal:2',
            'gaji_bersih' => 'decimal:2',
            'tanggal_generate' => 'datetime',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }
}
