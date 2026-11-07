<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f6f8f9;font-family:Inter,Arial,sans-serif;color:#343434">
    <div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #e4e4e4;border-radius:16px;overflow:hidden">
        <div style="background:#148ddd;background:linear-gradient(135deg,#148ddd,#0a4f86);padding:28px;text-align:center;color:#fff">
            <div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;opacity:.85">Tithe receipt</div>
            <h1 style="margin:8px 0 0;font-size:26px;font-weight:700">{{ $amount }}</h1>
            <div style="margin-top:6px;font-size:13px;opacity:.85">{{ $receipt->number }} · {{ $receipt->received_on->format('j F Y') }}</div>
        </div>
        <div style="padding:28px">
            <p style="line-height:1.6;margin-top:0">Dear {{ $firstName }},</p>
            <p style="line-height:1.6">Thank you for your tithe. Your receipt is attached to this email, and you can also find it under
                <strong>Tithe receipts</strong> on your profile in the ICA app and on the website.</p>
            <blockquote style="margin:22px 0;padding:14px 18px;border-left:4px solid #ffdd57;background:#fffbea;border-radius:8px;line-height:1.6;font-style:italic">
                “Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver.”
                <span style="display:block;margin-top:6px;font-style:normal;font-weight:600;color:#8d8d8d;font-size:13px">2 Corinthians 9:7</span>
            </blockquote>
            <p style="line-height:1.6;margin-bottom:0">God bless you,<br><strong>ICA Church</strong></p>
        </div>
        <div style="padding:16px 28px;border-top:1px solid #eee;text-align:center;font-size:12px;color:#8d8d8d">
            <a href="{{ $siteUrl }}" style="color:#148ddd;text-decoration:none">ICA App</a> · Sermons, prayer and church life in one place
        </div>
    </div>
</body>
</html>
