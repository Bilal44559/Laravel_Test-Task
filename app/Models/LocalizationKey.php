<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalizationKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
    ];

    public function localizations(): HasMany
    {
        return $this->hasMany(Localization::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_localization_key');
    }
}
