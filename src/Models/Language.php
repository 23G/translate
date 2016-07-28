<?php namespace DylanLamers\Translate\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /**
     * Scope a query to sort.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSort($query){
        return $query->orderBy('sort', 'desc');
    }
}
