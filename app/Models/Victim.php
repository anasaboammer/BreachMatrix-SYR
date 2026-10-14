<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Victim extends Model
{
    protected $fillable = [
        'hostname',
        'ip_address',
        'os',
        'encryption_key',
        'private_key',
        'status',
    ];

    public function encryptedFiles()
    {
        return $this->hasMany(EncryptedFile::class);
    }
}
