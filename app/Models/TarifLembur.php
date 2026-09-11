<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifLembur extends Model
{
    use HasFactory;

    protected $table = 'tarif_lembur';

    protected $primaryKey = 'id_tarif';

    const UPDATED_AT = null;

    protected $fillable = [
        'tarif_per_jam',
    ];

    protected function casts(): array
    {
        return [
            'tarif_per_jam' => 'decimal:2',
        ];
    }
}
