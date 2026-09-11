<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JabatanTunjangan extends Pivot
{
    protected $table = 'jabatan_tunjangan';

    protected $primaryKey = 'id_jabatantunjangan';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'id_jabatan',
        'id_tunjangan',
        'nominal',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id_jabatan');
    }

    public function tunjangan(): BelongsTo
    {
        return $this->belongsTo(Tunjangan::class, 'id_tunjangan', 'id_tunjangan');
    }
}