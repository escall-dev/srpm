<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    /** @use HasFactory<\Database\Factories\RequestFactory> */
    use HasFactory;

        /**
        * The attributes that are mass assignable.
        *
        * @var list<string>
        */
    protected $fillable = [
        'unit_id',
        'tenant_id',
        'type',
        'complaint_type',
        'complaint_topic',
        'complaint_priority',
        'reported_tenant_id',
        'reported_unit_id',
        'description',
        'image_path',
        'status',
        'owner_decision',
        'owner_decision_at',
    ];

    protected $casts = [
        'image_path' => 'array',
        'owner_decision_at' => 'datetime',
    ];

    /**
     * Get the unit that owns the request.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the tenant that owns the request.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the reported tenant for a specific complaint.
     */
    public function reportedTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'reported_tenant_id');
    }

    /**
     * Get the reported unit for a specific complaint.
     */
    public function reportedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'reported_unit_id');
    }

    public function complaintDemerit(): HasOne
    {
        return $this->hasOne(ComplaintDemerit::class);
    }
}
