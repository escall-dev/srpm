<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'description',
        'total_units',
    ];


    /**
     * Get the owner that owns the property.
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the units for the property.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get the expenses for the property.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the payment rule for the property.
     */
    public function paymentRule(): HasOne
    {
        return $this->hasOne(PaymentRule::class);
    }

    /**
     * Get the FAQ categories for the property.
     */
    public function faqCategories(): HasMany
    {
        return $this->hasMany(FaqCategory::class);
    }

    /**
     * Get the FAQs for the property.
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}