<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Victim;
use App\Models\Payload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class EncryptedFile extends Model
{
    protected $fillable = [
        'filename',
        'file_path',
        'victim_id',
        'payload_id',
    ];

    public function victim()
    {
        return $this->belongsTo(Victim::class);
    }

    public function payload()
    {
        return $this->belongsTo(Payload::class);
    }
}
