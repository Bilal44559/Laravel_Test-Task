<?php

namespace App\Models\Concerns;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait HasApiTokens
{
    public ?PersonalAccessToken $accessToken = null;

    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    public function createToken(string $name, array $abilities = ['*']): object
    {
        $plainTextToken = Str::random(40);

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
        ]);

        return (object) [
            'accessToken' => $token,
            'plainTextToken' => $token->getKey().'|'.$plainTextToken,
        ];
    }

    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->accessToken ?? null;
    }

    public function withAccessToken(PersonalAccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
