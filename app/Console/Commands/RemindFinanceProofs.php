<?php

namespace App\Console\Commands;

use App\Models\PaymentProof;
use App\Support\Notify;
use App\Support\Receipts;
use Illuminate\Console\Command;

/**
 * Daily (08:00 church time) nudge to the finance team while any proof of
 * payment is still waiting for a receipt. Stops once everything is receipted.
 */
class RemindFinanceProofs extends Command
{
    protected $signature = 'finance:remind';

    protected $description = 'Remind finance users about proofs of payment awaiting a receipt';

    public function handle(): int
    {
        $waiting = PaymentProof::whereIn('status', ['pending', 'acknowledged'])->count();
        if ($waiting === 0) {
            $this->info('Nothing waiting.');

            return self::SUCCESS;
        }

        $pending = PaymentProof::where('status', 'pending')->count();
        $body = $waiting === 1 ? '1 proof of payment is waiting for a receipt.' : "{$waiting} proofs of payment are waiting for a receipt.";
        if ($pending) {
            $body .= " {$pending} not yet acknowledged.";
        }

        $users = Receipts::financeUserIds();
        Notify::users($users, 'Proofs of payment to receipt', $body, 'pop', null, route('admin.finance.index', ['tab' => 'proofs']));
        $this->info("Reminded ".count($users)." finance user(s): {$body}");

        return self::SUCCESS;
    }
}
