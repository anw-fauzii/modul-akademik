<?php

namespace App\Models\Concerns;

use App\Support\Sqid;
use Illuminate\Database\Eloquent\Model;

/**
 * Mengaktifkan Sqid sebagai route key URL dan Route Model Binding yang terisolasi per model.
 */
trait HasSqidRouteKey
{
    public function getRouteKey(): mixed
    {
        $key = $this->getKey();

        if ($key === null || $key === '') {
            return parent::getRouteKey();
        }

        return Sqid::encodeFor(static::class, $key) ?? parent::getRouteKey();
    }

    public function getSqidAttribute(): string
    {
        return (string) $this->getRouteKey();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null && $field !== $this->getRouteKeyName()) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (! is_string($value)) {
            return null;
        }

        $decodedKey = Sqid::decodeFor(static::class, $value);

        if ($decodedKey === null) {
            return null;
        }

        return $this->resolveRouteBindingQuery($this, $decodedKey, $this->getRouteKeyName())->first();
    }
}
