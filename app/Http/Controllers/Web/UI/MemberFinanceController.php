<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Models\TitheReceipt;
use App\Support\Receipts;
use Illuminate\Http\Request;

/** A member's tithe receipt PDFs and proof-of-payment uploads (Profile → Tithe receipts). */
class MemberFinanceController extends Controller
{
    public function pdf(Request $request, TitheReceipt $receipt)
    {
        $user = $request->user();
        abort_unless(intval($user->member_id) === intval($receipt->member_id) || $user->hasScope('finance'), 403);

        return response(Receipts::pdfContents($receipt), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$receipt->number}.pdf\"",
        ]);
    }

    public function storeProof(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 409, 'Your account is not linked to a member profile yet.');

        $data = $request->validate([
            'file'    => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,heic,heif,webp,pdf'],
            'amount'  => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'paid_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
            'note'    => ['nullable', 'string', 'max:1000'],
        ], [], ['paid_on' => 'payment date', 'file' => 'proof of payment']);

        Receipts::submitProof($request->user(), $member, $request->file('file'), $data);

        return back()->with('success', 'Proof of payment sent to the finance team');
    }
}
