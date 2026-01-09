<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'title',
        'message',
        'data',
        'status',
        'sent_at',
        'read_at',
        'failure_reason',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Status Checks
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Actions
     */
    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Retry sending
     */
    public function retry(): void
    {
        // TODO: Implement retry logic based on channel

        $this->update([
            'status' => 'pending',
            'failure_reason' => null,
        ]);
    }

    /**
     * Static Factory Methods
     */
    public static function createForBooking(Booking $booking, string $type, string $channel = 'database'): self
    {
        $messages = [
            'booking_created' => [
                'title' => 'Booking Confirmed',
                'message' => "Your booking for {$booking->service->name} on {$booking->formatted_date} at {$booking->formatted_time} has been confirmed.",
            ],
            'booking_reminder' => [
                'title' => 'Booking Reminder',
                'message' => "Reminder: You have a booking for {$booking->service->name} tomorrow at {$booking->formatted_time}.",
            ],
            'booking_cancelled' => [
                'title' => 'Booking Cancelled',
                'message' => "Your booking for {$booking->service->name} on {$booking->formatted_date} has been cancelled.",
            ],
        ];

        $content = $messages[$type] ?? [
            'title' => 'Notification',
            'message' => 'You have a new notification',
        ];

        return static::create([
            'type' => $type,
            'notifiable_type' => Customer::class,
            'notifiable_id' => $booking->customer_id,
            'channel' => $channel,
            'title' => $content['title'],
            'message' => $content['message'],
            'data' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ],
            'status' => 'pending',
        ]);
    }

    /**
     * Bulk Operations
     */
    public static function markAllAsReadForNotifiable($notifiableType, $notifiableId): void
    {
        static::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function deleteOldNotifications($days = 90): int
    {
        return static::where('created_at', '<', now()->subDays($days))
            ->where('read_at', '!=', null)
            ->delete();
    }

    /**
     * Statistics
     */
    public static function getUnreadCountForNotifiable($notifiableType, $notifiableId): int
    {
        return static::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNull('read_at')
            ->count();
    }
}
