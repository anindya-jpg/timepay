<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PotonganRule extends Model
{
    use HasFactory;

    protected $table = 'potongan_rule';

    protected $primaryKey = 'id_potongan';

    public $timestamps = false;

    protected $fillable = [
        'nama_potongan',
        'jenis',
        'nominal',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }
}
