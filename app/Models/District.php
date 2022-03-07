<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * \App\Models\District
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Market[] $markets
 * @property-read int|null $markets_count
 * @method static \Database\Factories\DistrictFactory factory(...$parameters)
 * @method static Builder|District filter(array $filters)
 * @method static Builder|District newModelQuery()
 * @method static Builder|District newQuery()
 * @method static \Illuminate\Database\Query\Builder|District onlyTrashed()
 * @method static Builder|District query()
 * @method static Builder|District whereCreatedAt($value)
 * @method static Builder|District whereDeletedAt($value)
 * @method static Builder|District whereId($value)
 * @method static Builder|District whereName($value)
 * @method static Builder|District whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|District withTrashed()
 * @method static \Illuminate\Database\Query\Builder|District withoutTrashed()
 * @mixin \Eloquent
 */
class District extends \Eloquent
{
    use HasFactory, SoftDeletes;
    public function markets()
    {
        return $this->hasMany(Market::class);
    }

    public function setNameAttribute(string $name)
    {
        $this->attributes['name'] = ucwords(strtolower($name));
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when($filters['name'] ?? false, function (Builder $query, string $district) {
            $query->where('name', 'like', '%' . $district . '%');
        });
    }
}
