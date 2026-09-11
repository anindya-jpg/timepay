<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanJamKerja extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_jam_kerja';

    protected $primaryKey = 'id_jamkerja';

    public $timestamps = false;

    protected $fillable = [
        'jam_masuk_standar',
        'jam_pulang_standar',
    ];
}
