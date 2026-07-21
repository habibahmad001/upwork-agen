<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'category' => 'string',
    ];

    /**
     * Scope by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope crawler settings.
     */
    public function scopeCrawler($query)
    {
        return $query->category('crawler');
    }

    /**
     * Scope AI settings.
     */
    public function scopeAi($query)
    {
        return $query->category('ai');
    }

    /**
     * Scope notification settings.
     */
    public function scopeNotification($query)
    {
        return $query->category('notification');
    }

    /**
     * Scope filter settings.
     */
    public function scopeFilter($query)
    {
        return $query->category('filter');
    }

    /**
     * Scope system settings.
     */
    public function scopeSystem($query)
    {
        return $query->category('system');
    }

    /**
     * Get typed value.
     */
    public function getTypedValueAttribute()
    {
        $value = $this->attributes['value'];

        if ($value === null) {
            return null;
        }

        // Handle encrypted values
        if ($this->type === 'encrypted' && !empty($value)) {
            try {
                $value = Crypt::decrypt($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (float) $value : 0,
            'json' => json_decode($value, true) ?: [],
            'integer', 'int' => (int) $value,
            default => $value,
        };
    }

    /**
     * Set typed value.
     */
    public function setTypedValueAttribute($value): void
    {
        if ($this->type === 'encrypted' && !empty($value)) {
            $this->attributes['value'] = Crypt::encrypt($value);
        } elseif ($this->type === 'boolean') {
            $this->attributes['value'] = $value ? '1' : '0';
        } elseif ($this->type === 'json') {
            $this->attributes['value'] = json_encode($value);
        } elseif ($this->type === 'number' || $this->type === 'integer') {
            $this->attributes['value'] = (string) $value;
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get or create setting.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->typed_value;
    }

    /**
     * Set setting value.
     */
    public static function set(string $key, $value, string $type = 'string', string $category = 'system', string $description = ''): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value, // Will be processed by mutator
                'type' => $type,
                'category' => $category,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as array.
     */
    public static function allAsArray(): array
    {
        return self::all()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->typed_value])
            ->toArray();
    }

    /**
     * Get settings by category.
     */
    public static function byCategory(string $category): array
    {
        return self::category($category)
            ->get()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->typed_value])
            ->toArray();
    }
}
