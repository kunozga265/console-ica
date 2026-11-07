<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #2b2b2b; font-size: 11px; }
        .head { border-bottom: 3px solid #148ddd; padding-bottom: 12px; }
        .head td { vertical-align: middle; }
        .title { text-align: right; }
        .title h1 { margin: 0; font-size: 18px; color: #0a4f86; letter-spacing: 1px; }
        .muted { color: #7a7a7a; }
        .amount { margin: 22px 0 6px; padding: 16px; background: #f1f8fd; border-radius: 8px; text-align: center; }
        .amount .v { font-size: 24px; font-weight: bold; color: #0a4f86; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.rows td { padding: 7px 0; border-bottom: 1px solid #eee; }
        table.rows td.k { color: #7a7a7a; width: 38%; }
        .foot { margin-top: 26px; font-size: 10px; text-align: center; }
        .verse { margin-top: 18px; font-style: italic; text-align: center; color: #555; }
    </style>
</head>
<body>
    <table class="head" width="100%">
        <tr>
            <td>@if($logo)<img src="{{ $logo }}" style="height:42px">@else<strong style="font-size:16px">International Christian Assembly</strong>@endif</td>
            <td class="title">
                <h1>TITHE RECEIPT</h1>
                <div class="muted">No. {{ $receipt->number }}</div>
            </td>
        </tr>
    </table>

    <div class="amount">
        <div class="muted">Amount received</div>
        <div class="v">{{ $receipt->currency }} {{ number_format($receipt->amount, 2) }}</div>
    </div>

    <table class="rows">
        <tr><td class="k">Received from</td><td><strong>{{ $receipt->member?->fullName() }}</strong></td></tr>
        <tr><td class="k">Date received</td><td>{{ $receipt->received_on->format('j F Y') }}</td></tr>
        <tr><td class="k">Payment method</td><td>{{ $method }}</td></tr>
        @if($receipt->reference)<tr><td class="k">Reference</td><td>{{ $receipt->reference }}</td></tr>@endif
        @if($receipt->notes)<tr><td class="k">Notes</td><td>{{ $receipt->notes }}</td></tr>@endif
        <tr><td class="k">Issued by</td><td>{{ trim(($receipt->issuer?->first_name ?? '').' '.($receipt->issuer?->last_name ?? '')) ?: 'ICA Finance' }}</td></tr>
        <tr><td class="k">Issued on</td><td>{{ $receipt->created_at->timezone(config('app.timezone'))->format('j F Y, H:i') }}</td></tr>
    </table>

    <div class="verse">“God loves a cheerful giver.” — 2 Corinthians 9:7</div>
    <div class="foot muted">International Christian Assembly · Thank you for your faithful giving.<br>This receipt was generated electronically and is valid without a signature.</div>
</body>
</html>
