<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Victim;

class C2Command extends Model
{
    protected $fillable = [
        'command',
        'status',
        'victim_id',
    ];

    public function victim()
    {
        return $this->belongsTo(Victim::class);
    }
}
