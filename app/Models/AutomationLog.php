<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action_type',
        'reference_type',
        'reference_id',
        'payload',
        'executed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'executed_at' => 'datetime',
    ];
}
