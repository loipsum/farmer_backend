<?php

namespace App\Models;

use App\Observers\ItemObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * \App\Models\Item
 *
 * @property int $id
 * @property string $name
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entry[] $entries
 * @property-read int|null $entries_count
 * @method static \Database\Factories\ItemFactory factory(...$parameters)
 * @method static Builder|Item filter($filters)
 * @method static Builder|Item newModelQuery()
 * @method static Builder|Item newQuery()
 * @method static \Illuminate\Database\Query\Builder|Item onlyTrashed()
 * @method static Builder|Item query()
 * @method static Builder|Item whereCreatedAt($value)
 * @method static Builder|Item whereDeletedAt($value)
 * @method static Builder|Item whereId($value)
 * @method static Builder|Item whereName($value)
 * @method static Builder|Item whereUnit($value)
 * @method static Builder|Item whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Item withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Item withoutTrashed()
 * @mixin \Eloquent
 */
class Item extends \Eloquent
{
    use HasFactory, SoftDeletes;
    public $afterCommit = true;
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function setNameAttribute(string $name)
    {
        $this->attributes['name'] = ucwords(strtolower($name));
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when($filters['name'] ?? false, function (Builder $query, $name) {
            $query->where('name', 'like', "%$name%");
        })
            ->when($filters['sortBy'] ?? false, function (Builder $query, $sortBy) {
                $query->orderBy($sortBy);
            });
    }
}
