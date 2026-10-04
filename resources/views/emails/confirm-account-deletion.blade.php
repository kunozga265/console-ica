<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f6f8f9;font-family:Inter,Arial,sans-serif;color:#343434">
    <div style="max-width:520px;margin:0 auto;background:#fff;border:1px solid #e4e4e4;border-radius:16px;padding:28px">
        <h2 style="margin:0 0 12px;font-size:20px">Delete your ICA App account?</h2>
        <p style="line-height:1.6">Hi {{ $name }},</p>
        <p style="line-height:1.6">We received a request to delete your ICA App account. If this was you, confirm below.
            This permanently deletes your account and your notes, highlights, bookmarks, saved sermons and other app data.</p>
        <p style="margin:24px 0">
            <a href="{{ $url }}" style="background:#e4343e;color:#fff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;display:inline-block">Review and delete my account</a>
        </p>
        <p style="line-height:1.6;color:#8d8d8d;font-size:13px">This link expires in {{ $minutes }} minutes. If you didn't ask for this, ignore this email — your account stays as it is.</p>
    </div>
</body>
</html>
