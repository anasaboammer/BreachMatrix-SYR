<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payload extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_os',
        'encryption_type',
        'icon_path',
        'target_directories',
        'status',
        'description',
    ];

    protected $casts = [
        'target_directories' => 'array',
    ];
}
