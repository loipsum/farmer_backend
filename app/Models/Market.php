<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * \App\Models\Market
 *
 * @property int $id
 * @property string $name
 * @property string $location
 * @property int $district_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\District $district
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entry[] $entries
 * @property-read int|null $entries_count
 * @method static \Database\Factories\MarketFactory factory(...$parameters)
 * @method static Builder|Market filter(array $filters)
 * @method static Builder|Market newModelQuery()
 * @method static Builder|Market newQuery()
 * @method static \Illuminate\Database\Query\Builder|Market onlyTrashed()
 * @method static Builder|Market query()
 * @method static Builder|Market whereCreatedAt($value)
 * @method static Builder|Market whereDeletedAt($value)
 * @method static Builder|Market whereDistrictId($value)
 * @method static Builder|Market whereId($value)
 * @method static Builder|Market whereLocation($value)
 * @method static Builder|Market whereName($value)
 * @method static Builder|Market whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Market withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Market withoutTrashed()
 * @mixin \Eloquent
 */
class Market extends \Eloquent
{
    use HasFactory, SoftDeletes;
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }
    public function district()
    {
        return $this->belongsTo(District::class);
    }
    public function setNameAttribute(string $name)
    {
        $this->attributes['name'] = ucwords(strtolower($name));
    }
    public function setLocationAttribute(string $location)
    {
        $this->attributes['location'] = ucwords(strtolower($location));
    }
    public function scopeFilter(Builder $query, array $filters)
    {
        $query
            ->when($filters['name'] ?? false, function (Builder $query, string $name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($filters['location'] ?? false, function (Builder $query, string $location) {
                $query->where('location', 'like', '%' . $location . '%');
            })
            ->when($filters['district'] ?? false, function (Builder $query, string $district) {
                $query->whereHas('district', function (Builder $query) use ($district) {
                    $query->where('name', 'like', '%' . $district . '%');
                });
            });
    }
}
