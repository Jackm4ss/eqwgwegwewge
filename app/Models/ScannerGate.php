<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScannerGate extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
