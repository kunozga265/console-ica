<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A member's proof of payment (photo or PDF) for the finance team to receipt. */
class PaymentProof extends Model
{
    protected $fillable = ['member_id', 'user_id', 'file_path', 'file_name', 'mime', 'amount', 'paid_on', 'note', 'status', 'acknowledged_by', 'acknowledged_at'];

    protected $casts = ['paid_on' => 'date', 'acknowledged_at' => 'datetime', 'amount' => 'float'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receipt()
    {
        return $this->hasOne(TitheReceipt::class);
    }
}
