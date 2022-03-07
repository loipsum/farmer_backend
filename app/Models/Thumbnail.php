<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Thumbnail
 *
 * @property int $id
 * @property int $entry_id
 * @property string $url
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Entry $entry
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail query()
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thumbnail whereUrl($value)
 * @mixin \Eloquent
 */
class Thumbnail extends \Eloquent
{
    use HasFactory, SoftDeletes;
    public function getUrlAttribute($url)
    {
        return asset($url);
    }
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
}
