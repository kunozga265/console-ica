<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A way to give (bank account or mobile-money number), shown on the Give page. */
class GivingOption extends Model
{
    protected $fillable = ['type', 'name', 'account_name', 'account_number', 'branch', 'swift_code', 'instructions', 'sort_order', 'active'];

    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
