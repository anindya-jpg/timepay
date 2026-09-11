<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanLokasi extends Model
{
    protected $table = 'pengaturan_lokasi';

    protected $primaryKey = 'id_lokasi';

    public $timestamps = false;

    protected $fillable = [
        'nama_lokasi',
        'latitude',
        'longitude',
        'radius_meter',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}