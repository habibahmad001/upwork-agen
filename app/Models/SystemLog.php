<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'message',
        'context',
        'source',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Disable timestamps (we use created_at only).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Scope info logs.
     */
    public function scopeInfo($query)
    {
        return $query->where('type', 'info');
    }

    /**
     * Scope warning logs.
     */
    public function scopeWarning($query)
    {
        return $query->where('type', 'warning');
    }

    /**
     * Scope error logs.
     */
    public function scopeError($query)
    {
        return $query->where('type', 'error');
    }

    /**
     * Scope debug logs.
     */
    public function scopeDebug($query)
    {
        return $query->where('type', 'debug');
    }

    /**
     * Scope by source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope crawler logs.
     */
    public function scopeCrawler($query)
    {
        return $query->where('source', 'crawler');
    }

    /**
     * Scope AI logs.
     */
    public function scopeAi($query)
    {
        return $query->where('source', 'ai');
    }

    /**
     * Scope notification logs.
     */
    public function scopeNotification($query)
    {
        return $query->where('source', 'notification');
    }

    /**
     * Scope recent logs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Scope old logs (for cleanup).
     */
    public function scopeOld($query, string $type, int $days)
    {
        return $query->where('type', $type)
            ->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Create info log.
     */
    public static function createInfo(string $message, array $context = [], string $source = 'system'): self
    {
        return self::create([
            'type' => 'info',
            'message' => $message,
            'context' => $context,
            'source' => $source,
            'created_at' => now(),
        ]);
    }

    /**
     * Create warning log.
     */
    public static function createWarning(string $message, array $context = [], string $source = 'system'): self
    {
        return self::create([
            'type' => 'warning',
            'message' => $message,
            'context' => $context,
            'source' => $source,
            'created_at' => now(),
        ]);
    }

    /**
     * Create error log.
     */
    public static function createError(string $message, array $context = [], string $source = 'system'): self
    {
        return self::create([
            'type' => 'error',
            'message' => $message,
            'context' => $context,
            'source' => $source,
            'created_at' => now(),
        ]);
    }

    /**
     * Create debug log.
     */
    public static function createDebug(string $message, array $context = [], string $source = 'system'): self
    {
        return self::create([
            'type' => 'debug',
            'message' => $message,
            'context' => $context,
            'source' => $source,
            'created_at' => now(),
        ]);
    }


    /**
     * Log with type.
     */
    public static function log(string $type, string $message, array $context = [], string $source = 'system'): self
    {
        return self::create([
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'source' => $source,
            'created_at' => now(),
        ]);
    }
}
