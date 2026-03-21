<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationFactory> */
    use HasFactory;

    public const TYPE_RENT_DUE_REMINDER = 'rent_due_reminder';
    public const TYPE_DEMERIT_WARNING = 'demerit_warning';
    public const TYPE_DEMERIT_FINAL_WARNING = 'demerit_final_warning';
    public const TYPE_TERMINATION_NOTICE = 'termination_notice';
    public const TYPE_COMPLAINT_DECISION = 'complaint_decision';

        /**
        * The attributes that are mass assignable.
        *
        * @var list<string>
        */
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'is_read',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function notifyOnce(int $userId, string $type, string $message): self
    {
        return static::firstOrCreate([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ], [
            'is_read' => false,
        ]);
    }
}
