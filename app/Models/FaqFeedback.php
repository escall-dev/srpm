<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\FaqFeedbackFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'faq_id',
        'tenant_id',
        'vote',
    ];

    /**
     * Get the FAQ that owns the feedback.
     */
    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }

    /**
     * Get the tenant that owns the feedback.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
