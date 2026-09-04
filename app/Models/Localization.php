<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Localization extends Model
{
    use HasFactory;

    protected $fillable = [
        'localization_key_id',
        'language_id',
        'content',
        'status',
        'context',
        'version',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function localizationKey(): BelongsTo
    {
        return $this->belongsTo(LocalizationKey::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
