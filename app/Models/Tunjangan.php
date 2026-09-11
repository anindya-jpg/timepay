<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tunjangan extends Model
{
    use HasFactory;

    protected $table = 'tunjangan';

    protected $primaryKey = 'id_tunjangan';

    public $timestamps = false;

    protected $fillable = [
        'nama_tunjangan',
        'keterangan',
    ];

    public function jabatans(): BelongsToMany
    {
        return $this->belongsToMany(
            Jabatan::class,
            'jabatan_tunjangan',
            'id_tunjangan',
            'id_jabatan',
            'id_tunjangan',
            'id_jabatan'
        )
            ->using(JabatanTunjangan::class)
            ->withPivot(['id_jabatantunjangan', 'nominal']);
    }
}
