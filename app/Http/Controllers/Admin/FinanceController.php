<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PaymentProof;
use App\Models\TitheReceipt;
use App\Support\Receipts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Tithe receipts and members' proofs of payment. Finance scope (accountants) and super users. */
class FinanceController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'proofs' ? 'proofs' : 'receipts';
        $search = trim((string) $request->query('search'));
        $memberSearch = fn ($q) => $q->whereHas('member', fn ($m) => $m->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]));

        $receipts = TitheReceipt::with(['member', 'issuer'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $memberSearch($w)->orWhere('number', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")))
            ->orderByDesc('id')->paginate(self::PER_PAGE, ['*'], 'page')->withQueryString();

        $proofs = PaymentProof::with(['member', 'receipt'])
            ->when($search !== '', $memberSearch)
            ->orderByRaw("FIELD(status, 'pending', 'acknowledged', 'receipted')")->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], 'proofs_page')->withQueryString();

        $paging = fn ($p) => ['page' => $p->currentPage(), 'lastPage' => $p->lastPage(), 'total' => $p->total(), 'from' => $p->firstItem(), 'to' => $p->lastItem(), 'pageName' => $p->getPageName()];

        return Inertia::render('Admin/Finance', [
            'tab'      => $tab,
            'filters'  => (object) array_filter(['search' => $search]),
            'receipts' => collect($receipts->items())->map(fn ($r) => Receipts::receiptRow($r)),
            'receiptsPaging' => $paging($receipts),
            'proofs'   => collect($proofs->items())->map(fn ($p) => Receipts::proofRow($p)),
            'proofsPaging' => $paging($proofs),
            'counts'   => [
                'waiting'   => PaymentProof::whereIn('status', ['pending', 'acknowledged'])->count(),
                'thisMonth' => (float) TitheReceipt::where('received_on', '>=', now(config('app.timezone'))->startOfMonth()->toDateString())->sum('amount'),
                'receipts'  => TitheReceipt::count(),
            ],
            'methods'  => collect(TitheReceipt::METHODS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
        ]);
    }

    /** Member picker for a new receipt (JSON). */
    public function members(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(Member::with('cell')
            ->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"])
                ->orWhere('phone_number_airtel', 'like', "%{$q}%")->orWhere('phone_number_tnm', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->orderBy('first_name')->limit(12)->get()
            ->map(fn (Member $m) => ['id' => $m->id, 'name' => $m->fullName(), 'avatar' => $m->avatar, 'cell' => $m->cell?->name, 'hasAccount' => $m->users()->exists()]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'memberId'   => ['required', 'integer', 'exists:members,id'],
            'amount'     => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'receivedOn' => ['required', 'date_format:Y-m-d', 'before_or_equal:tomorrow'],
            'method'     => ['required', Rule::in(array_keys(TitheReceipt::METHODS))],
            'reference'  => ['nullable', 'string', 'max:191'],
            'notes'      => ['nullable', 'string', 'max:1000'],
            'proofId'    => ['nullable', 'integer', 'exists:payment_proofs,id'],
        ], [], ['memberId' => 'member', 'receivedOn' => 'date received', 'proofId' => 'proof of payment']);

        if (! empty($data['proofId'])) {
            $proof = PaymentProof::findOrFail($data['proofId']);
            abort_if($proof->status === 'receipted', 422, 'That proof of payment already has a receipt.');
            abort_unless(intval($proof->member_id) === intval($data['memberId']), 422, 'That proof of payment belongs to another member.');
        }

        $receipt = Receipts::issue([
            'member_id'        => $data['memberId'],
            'amount'           => $data['amount'],
            'received_on'      => $data['receivedOn'],
            'method'           => $data['method'],
            'reference'        => $data['reference'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'payment_proof_id' => $data['proofId'] ?? null,
        ], $request->user());

        return back()->with('success', "Receipt {$receipt->number} sent");
    }

    public function resend(TitheReceipt $receipt)
    {
        Receipts::deliver($receipt);

        return back()->with('success', "Receipt {$receipt->number} sent again");
    }

    public function pdf(TitheReceipt $receipt)
    {
        return response(Receipts::pdfContents($receipt), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "inline; filename=\"{$receipt->number}.pdf\""]);
    }

    public function acknowledge(Request $request, PaymentProof $proof)
    {
        if ($proof->status === 'pending') {
            $proof->update(['status' => 'acknowledged', 'acknowledged_by' => $request->user()->id, 'acknowledged_at' => now()]);
        }

        return back()->with('success', 'Marked as seen');
    }

    /** The uploaded slip/screenshot, streamed from the private receipts disk. */
    public function proofFile(PaymentProof $proof)
    {
        $disk = Receipts::disk();
        abort_unless($disk->exists($proof->file_path), 404);

        return response($disk->get($proof->file_path), 200, [
            'Content-Type'        => $proof->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($proof->file_name ?: basename($proof->file_path)).'"',
        ]);
    }
}
