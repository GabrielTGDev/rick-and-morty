<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    /**
     * The characters that are residents of this location.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Character::class, 'current_location_id');
    }
}
