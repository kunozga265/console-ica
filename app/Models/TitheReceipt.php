<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A tithe receipt issued to a member by someone with the finance scope. */
class TitheReceipt extends Model
{
    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'mobile' => 'Mobile money'];

    protected $fillable = ['number', 'member_id', 'amount', 'currency', 'received_on', 'method', 'reference', 'notes', 'issued_by', 'pdf_path', 'payment_proof_id'];

    protected $casts = ['received_on' => 'date', 'amount' => 'float'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function proof()
    {
        return $this->belongsTo(PaymentProof::class, 'payment_proof_id');
    }
}
