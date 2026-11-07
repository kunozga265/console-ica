<?php

namespace App\Http\Controllers\API\V1_4;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\TitheReceipt;
use App\Support\Receipts;
use Illuminate\Http\Request;

/** A member's own tithe receipts and proofs of payment (the app's Tithe receipts screen). */
class FinanceController extends Controller
{
    public function receipts(Request $request)
    {
        $memberId = $request->user()->member_id;
        $receipts = $memberId ? TitheReceipt::with(['member', 'issuer'])->where('member_id', $memberId)->orderByDesc('received_on')->orderByDesc('id')->get() : collect();

        return response()->json(['data' => $receipts->map(fn ($r) => Receipts::receiptRow($r))->values()]);
    }

    public function pdf(Request $request, TitheReceipt $receipt)
    {
        $user = $request->user();
        abort_unless(intval($user->member_id) === intval($receipt->member_id) || $user->hasScope('finance'), 403);

        return response(Receipts::pdfContents($receipt), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$receipt->number}.pdf\"",
        ]);
    }

    public function proofs(Request $request)
    {
        $memberId = $request->user()->member_id;
        $proofs = $memberId ? PaymentProof::with(['member', 'receipt'])->where('member_id', $memberId)->latest()->limit(50)->get() : collect();

        return response()->json(['data' => $proofs->map(fn ($p) => Receipts::proofRow($p))->values()]);
    }

    public function storeProof(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 409, 'Create your member profile first (Profile → My details).');

        $data = $request->validate([
            'file'    => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,heic,heif,webp,pdf'],
            'amount'  => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'paid_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
            'note'    => ['nullable', 'string', 'max:1000'],
        ], [], ['paid_on' => 'payment date']);

        $proof = Receipts::submitProof($request->user(), $member, $request->file('file'), $data);

        return response()->json(['data' => Receipts::proofRow($proof->load(['member', 'receipt']))], 201);
    }
}
