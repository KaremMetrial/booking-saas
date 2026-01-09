<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'service_id',
        'staff_id',
        'rating',
        'service_rating',
        'staff_rating',
        'cleanliness_rating',
        'value_rating',
        'comment',
        'reply',
        'replied_at',
        'is_verified',
        'is_published',
        'helpful_count',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            // Update staff average rating
            if ($review->staff) {
                $review->staff->updateStats();
            }

            // Update service stats
            $review->service->updateStats();

            // Award loyalty points to customer
            $review->customer->addPoints(10); // 10 points for leaving a review
        });
    }

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithReply($query)
    {
        return $query->whereNotNull('reply');
    }

    public function scopeWithoutReply($query)
    {
        return $query->whereNull('reply');
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRated($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Status Checks
     */
    public function isPublished(): bool
    {
        return $this->is_published;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function hasReply(): bool
    {
        return $this->reply !== null;
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Actions
     */
    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }

    public function unpublish(): void
    {
        $this->update(['is_published' => false]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function replyTo(string $reply): void
    {
        $this->update([
            'reply' => $reply,
            'replied_at' => now(),
        ]);

        // Notify customer
        // TODO: Send notification
    }

    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Accessors
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }

    public function getRatingColorAttribute(): string
    {
        if ($this->rating >= 4) return 'success';
        if ($this->rating >= 3) return 'warning';
        return 'danger';
    }

    public function getAverageDetailedRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->service_rating,
            $this->staff_rating,
            $this->cleanliness_rating,
            $this->value_rating,
        ]);

        if (empty($ratings)) {
            return $this->rating;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    /**
     * Sentiment Analysis (Simple)
     */
    public function getSentimentAttribute(): string
    {
        if (!$this->comment) {
            return 'neutral';
        }

        $positiveWords = ['great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'perfect', 'love', 'best', 'awesome'];
        $negativeWords = ['bad', 'terrible', 'awful', 'poor', 'worst', 'horrible', 'disappointing', 'unprofessional'];

        $comment = strtolower($this->comment);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($comment, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($comment, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Static Helpers
     */
    public static function getAverageRatingForService($serviceId): float
    {
        return static::where('service_id', $serviceId)
            ->where('is_published', true)
            ->avg('rating') ?? 0;
    }

    public static function getAverageRatingForStaff($staffId): float
    {
        return static::where('staff_id', $staffId)
            ->where('is_published', true)
            ->avg('staff_rating') ?? 0;
    }

    public static function getTotalReviewsForService($serviceId): int
    {
        return static::where('service_id', $serviceId)
            ->where('is_published', true)
            ->count();
    }

    public static function getRatingDistribution($serviceId = null): array
    {
        $query = static::where('is_published', true);

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $counts = $query->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return array_merge($distribution, $counts);
    }
}
