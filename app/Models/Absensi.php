<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $primaryKey = 'id_absensi';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_karyawan',
        'tanggal',

        // =========================
        // ABSEN MASUK
        // =========================
        'jam_masuk',
        'foto_masuk',
        'lokasi_masuk_lat',
        'lokasi_masuk_lng',
        'jarak_masuk_meter',

        // =========================
        // ABSEN PULANG
        // =========================
        'jam_pulang',
        'foto_pulang',
        'lokasi_pulang_lat',
        'lokasi_pulang_lng',
        'jarak_pulang_meter',

        // =========================
        // STATUS ABSENSI
        // =========================
        'status',

        // Ditambahkan: dipakai fitur pengajuan Izin/Sakit
        // (AbsensiKaryawanController::storeIzinSakit).
        // Tanpa ini, updateOrCreate() diam-diam mengabaikan
        // kedua field ini walau di kode sudah dikirim.
        'keterangan',
        'file_pendukung',

        'menit_telat',
        'menit_pulang_cepat',

        // =========================
        // LEMBUR
        // =========================
        'jam_lembur',
        'keterangan_lembur',
        'status_approval_lembur',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',

            'jam_lembur' => 'decimal:2',

            'menit_telat' => 'integer',

            'menit_pulang_cepat' => 'integer',

            'jarak_masuk_meter' => 'decimal:2',

            'jarak_pulang_meter' => 'decimal:2',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(
            Karyawan::class,
            'id_karyawan',
            'id_karyawan'
        );
    }

    /**
     * Scope untuk data lembur yang masih menunggu approval,
     * dipakai di halaman "Approval Lembur".
     */
    public function scopePendingLembur($query)
    {
        return $query->where(
            'status_approval_lembur',
            'pending'
        );
    }
}