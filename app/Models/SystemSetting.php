<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = self::find($key);

        if (! $row || $row->value === null || $row->value === '') {
            return $default;
        }

        $v = $row->value;
        if (in_array($key, ['ai_exam_companion_enabled', 'online_release_enabled', 'consultation_enabled'], true)) {
            return filter_var($v, FILTER_VALIDATE_BOOLEAN);
        }

        return $v;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
    }

    /**
     * Whether the AI exam companion feature is enabled.
     */
    public static function aiCompanionEnabled(): bool
    {
        return (bool) self::get('ai_exam_companion_enabled', false);
    }

    /**
     * Whether the consultation feature is enabled. Default true for backward compatibility.
     */
    public static function consultationEnabled(): bool
    {
        return (bool) self::get('consultation_enabled', true);
    }

    /**
     * Default system prompt / persona when none is set (T3.2).
     */
    public static function defaultPersonaPrompt(): string
    {
        return 'You are a helpful academic counselor. Base your advice only on the data provided. Do not invent statistics. If the data does not cover a question, say so.';
    }

    /**
     * Get the AI companion persona prompt (system instructions). Returns default when empty.
     */
    public static function personaPrompt(): string
    {
        $value = self::get('ai_companion_persona', null);
        if ($value === null || trim((string) $value) === '') {
            return self::defaultPersonaPrompt();
        }

        return trim((string) $value);
    }
}
