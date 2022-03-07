<?php

namespace App\Models;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Models\Entry
 *
 * @property int $id
 * @property int $user_id
 * @property int $item_id
 * @property int $market_id
 * @property float $cost
 * @property float $quantity
 * @property float $price_per_kg
 * @property mixed|null $from
 * @property mixed|null $to
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property mixed|null $created_at
 * @property mixed|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\Market $market
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\EntryFactory factory(...$parameters)
 * @method static Builder|Entry filter(array $filters)
 * @method static Builder|Entry newModelQuery()
 * @method static Builder|Entry newQuery()
 * @method static \Illuminate\Database\Query\Builder|Entry onlyTrashed()
 * @method static Builder|Entry query()
 * @method static Builder|Entry whereCost($value)
 * @method static Builder|Entry whereCreatedAt($value)
 * @method static Builder|Entry whereDeletedAt($value)
 * @method static Builder|Entry whereFrom($value)
 * @method static Builder|Entry whereId($value)
 * @method static Builder|Entry whereItemId($value)
 * @method static Builder|Entry whereMarketId($value)
 * @method static Builder|Entry wherePricePerKg($value)
 * @method static Builder|Entry whereQuantity($value)
 * @method static Builder|Entry whereTo($value)
 * @method static Builder|Entry whereUpdatedAt($value)
 * @method static Builder|Entry whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|Entry withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Entry withoutTrashed()
 * @mixin \Eloquent
 * @property-read \App\Models\Thumbnail|null $thumbnail
 * @property-read \App\Models\Photo|null $photo
 */
class Entry extends \Eloquent
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'created_at' => 'date:D, d-M-Y',
        'updated_at' => 'date:D, d-M-Y',
        'to' => 'date:D, d-M-Y',
        'from' => 'date:D, d-M-Y'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function market()
    {
        return $this->belongsTo(Market::class);
    }
    public function photo()
    {
        return $this->hasOne(Photo::class);
    }
    public function thumbnail()
    {
        return $this->hasOne(Thumbnail::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function setFromAttribute(string $entryDate)
    {
        if (!$entryDate == '')
            $this->attributes['from'] = Carbon::createFromFormat('Y-m-d', $entryDate)->startOfWeek(Carbon::SUNDAY)->toDateTimeString();
    }

    public function setToAttribute($entryDate)
    {
        if (!$entryDate == '')
            $this->attributes['to'] = Carbon::createFromFormat('Y-m-d', $entryDate)->endOfWeek(Carbon::SATURDAY)->toDateTimeString();
    }
}
