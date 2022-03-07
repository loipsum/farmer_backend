<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * \App\Models\Photo
 *
 * @property int $id
 * @property int $entry_id
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Entry $entry
 * @method static Builder|Photo filter(array $filters)
 * @method static Builder|Photo newModelQuery()
 * @method static Builder|Photo newQuery()
 * @method static QueryBuilder|Photo onlyTrashed()
 * @method static Builder|Photo query()
 * @method static Builder|Photo whereCreatedAt($value)
 * @method static Builder|Photo whereDeletedAt($value)
 * @method static Builder|Photo whereEntryId($value)
 * @method static Builder|Photo whereId($value)
 * @method static Builder|Photo whereUpdatedAt($value)
 * @method static Builder|Photo whereUrl($value)
 * @method static QueryBuilder|Photo withTrashed()
 * @method static QueryBuilder|Photo withoutTrashed()
 * @mixin \Eloquent
 * @method static \Database\Factories\PhotoFactory factory(...$parameters)
 */
class Photo extends \Eloquent
{
    use HasFactory, SoftDeletes;
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function getUrlAttribute($url)
    {
        return asset($url);
    }
    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when($filters['entry_id'] ?? false, function (Builder $query, string $entry_id) {
            $query->where('entry_id', $entry_id);
        });
    }
}
