<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f6f8f9;font-family:Inter,Arial,sans-serif;color:#343434">
    <div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #e4e4e4;border-radius:16px;overflow:hidden">
        <div style="background:#148ddd;background:linear-gradient(135deg,#148ddd,#0a4f86);padding:32px 28px;text-align:center;color:#fff">
            <div style="font-size:40px;line-height:1">🎂</div>
            <h1 style="margin:14px 0 0;font-size:24px;font-weight:700">Happy birthday, {{ $firstName }}!</h1>
        </div>
        <div style="padding:28px">
            <p style="line-height:1.6;margin-top:0">Dear {{ $firstName }},</p>
            <p style="line-height:1.6">Your International Christian Assembly family is celebrating you today. We thank God for your life
                and for all that He has done in and through you this past year.</p>
            <blockquote style="margin:22px 0;padding:14px 18px;border-left:4px solid #ffdd57;background:#fffbea;border-radius:8px;line-height:1.6;font-style:italic">
                “The LORD bless you and keep you; the LORD make His face shine upon you, and be gracious to you;
                the LORD lift up His countenance upon you, and give you peace.”
                <span style="display:block;margin-top:6px;font-style:normal;font-weight:600;color:#8d8d8d;font-size:13px">Numbers 6:24–26</span>
            </blockquote>
            <p style="line-height:1.6">May this new year of your life be filled with His grace, joy and purpose.</p>
            <p style="line-height:1.6;margin-bottom:0">With love,<br><strong>ICA Church</strong></p>
        </div>
        <div style="padding:16px 28px;border-top:1px solid #eee;text-align:center;font-size:12px;color:#8d8d8d">
            <a href="{{ $siteUrl }}" style="color:#148ddd;text-decoration:none">ICA App</a> · Sermons, prayer and church life in one place
        </div>
    </div>
</body>
</html>
