<?php

namespace App\Support;

use App\Mail\TitheReceiptIssued;
use App\Models\Member;
use App\Models\PaymentProof;
use App\Models\TitheReceipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tithe receipts and proofs of payment. Receipts are issued by users with the
 * finance scope; each gets a yearly number (ICA-2026-00001), a PDF on the
 * "receipts" disk, and an inbox + push + email to the member.
 */
class Receipts
{
    public static function disk()
    {
        return Storage::disk('receipts');
    }

    /** Users who handle finance: the finance scope, or super users. */
    public static function financeUserIds(): array
    {
        return User::with('roles')->get()->filter(fn (User $u) => $u->hasScope('finance'))->pluck('id')->all();
    }

    /** @param array{member_id:int, amount:float, received_on:string, method:string, reference?:?string, notes?:?string, payment_proof_id?:?int} $data */
    public static function issue(array $data, User $issuer): TitheReceipt
    {
        $receipt = DB::transaction(function () use ($data, $issuer) {
            $year = substr($data['received_on'], 0, 4);
            $last = TitheReceipt::where('number', 'like', "ICA-{$year}-%")->lockForUpdate()->orderByDesc('number')->value('number');
            $next = $last ? intval(substr($last, -5)) + 1 : 1;

            $receipt = TitheReceipt::create($data + [
                'number'    => sprintf('ICA-%s-%05d', $year, $next),
                'currency'  => 'MWK',
                'issued_by' => $issuer->id,
            ]);

            if ($receipt->payment_proof_id) {
                PaymentProof::whereKey($receipt->payment_proof_id)->update([
                    'status'          => 'receipted',
                    'acknowledged_by' => DB::raw('coalesce(acknowledged_by, '.intval($issuer->id).')'),
                    'acknowledged_at' => DB::raw('coalesce(acknowledged_at, now())'),
                ]);
            }

            return $receipt;
        });

        $receipt->update(['pdf_path' => self::storePdf($receipt)]);
        self::deliver($receipt);

        return $receipt;
    }

    public static function pdf(TitheReceipt $receipt): string
    {
        $receipt->loadMissing(['member', 'issuer']);

        return Pdf::loadView('pdf.tithe-receipt', [
            'receipt' => $receipt,
            'method'  => TitheReceipt::METHODS[$receipt->method] ?? $receipt->method,
            'logo'    => is_readable(public_path('images/logo-full.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-full.png'))) : null,
        ])->setPaper('a5')->output();
    }

    private static function storePdf(TitheReceipt $receipt): string
    {
        $path = "tithe/{$receipt->received_on->format('Y')}/{$receipt->number}.pdf";
        self::disk()->put($path, self::pdf($receipt));

        return $path;
    }

    /** The PDF bytes (regenerated if the stored copy is missing). */
    public static function pdfContents(TitheReceipt $receipt): string
    {
        if ($receipt->pdf_path && self::disk()->exists($receipt->pdf_path)) {
            return self::disk()->get($receipt->pdf_path);
        }
        $receipt->update(['pdf_path' => self::storePdf($receipt)]);

        return self::disk()->get($receipt->pdf_path);
    }

    /** Inbox + push to the member's account(s), and the email with the PDF. */
    public static function deliver(TitheReceipt $receipt): void
    {
        $receipt->loadMissing('member.users');
        $member = $receipt->member;
        $amount = 'MWK '.number_format($receipt->amount, 2);

        Notify::users(
            $member->users->pluck('id'),
            'Tithe receipt '.$receipt->number,
            "We received your tithe of {$amount}. Thank you for your faithful giving.",
            'receipt',
            (string) $receipt->id,
            route('ui.profile').'#receipts'
        );

        $email = collect([$member->email])->merge($member->users->pluck('email'))
            ->first(fn ($e) => $e && filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($email) {
            try {
                Mail::to($email)->send(new TitheReceiptIssued($receipt, self::pdfContents($receipt)));
            } catch (\Throwable $e) {
                Log::error('Tithe receipt email failed', ['receipt' => $receipt->number, 'error' => $e->getMessage()]);
            }
        }
    }

    /** A member's upload; finance users are notified straight away (and daily until it's receipted). */
    public static function submitProof(User $user, Member $member, UploadedFile $file, array $data): PaymentProof
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = self::disk()->putFileAs('proofs/'.now()->format('Y/m'), $file, Str::uuid().'.'.$ext);

        $proof = PaymentProof::create([
            'member_id' => $member->id,
            'user_id'   => $user->id,
            'file_path' => $path,
            'file_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'mime'      => $file->getMimeType(),
            'amount'    => $data['amount'] ?? null,
            'paid_on'   => $data['paid_on'] ?? null,
            'note'      => $data['note'] ?? null,
            'status'    => 'pending',
        ]);

        $amount = $proof->amount ? ' of MWK '.number_format($proof->amount, 2) : '';
        Notify::users(self::financeUserIds(), 'New proof of payment', "{$member->fullName()} sent a proof of payment{$amount}.", 'pop', (string) $proof->id, route('admin.finance.index', ['tab' => 'proofs']));

        return $proof;
    }

    public static function proofRow(PaymentProof $p): array
    {
        return [
            'id'          => $p->id,
            'member'      => $p->member ? ['id' => $p->member->id, 'code' => $p->member->code, 'name' => $p->member->fullName(), 'avatar' => $p->member->avatar] : null,
            'fileName'    => $p->file_name,
            'mime'        => $p->mime,
            'isImage'     => str_starts_with((string) $p->mime, 'image/'),
            'amount'      => $p->amount,
            'paidOn'      => $p->paid_on?->toDateString(),
            'note'        => $p->note,
            'status'      => $p->status,
            'submittedAt' => $p->created_at?->getTimestamp() * 1000,
            'receiptId'   => $p->receipt?->id,
        ];
    }

    public static function receiptRow(TitheReceipt $r): array
    {
        return [
            'id'         => $r->id,
            'number'     => $r->number,
            'member'     => $r->member ? ['id' => $r->member->id, 'code' => $r->member->code, 'name' => $r->member->fullName(), 'avatar' => $r->member->avatar] : null,
            'amount'     => $r->amount,
            'currency'   => $r->currency,
            'receivedOn' => $r->received_on?->toDateString(),
            'method'     => $r->method,
            'methodName' => TitheReceipt::METHODS[$r->method] ?? $r->method,
            'reference'  => $r->reference,
            'notes'      => $r->notes,
            'issuedBy'   => $r->issuer ? trim("{$r->issuer->first_name} {$r->issuer->last_name}") : null,
            'proofId'    => $r->payment_proof_id,
            'createdAt'  => $r->created_at?->getTimestamp() * 1000,
        ];
    }
}
