<!DOCTYPE html>
<html>
<body style="margin:0;background:#faf6ee;font-family:Arial,Helvetica,sans-serif;color:#1c2321">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden">
            <tr><td style="background:#1f4d3a;padding:20px 28px;color:#fff;font-size:22px;font-weight:bold">Pearl<span style="color:#e0a526">360</span> <span style="font-size:13px;font-weight:normal;color:#cfe0d7">Uganda Parks</span></td></tr>
            <tr><td style="height:5px;background:linear-gradient(90deg,#000 33%,#fcdc04 33%,#fcdc04 66%,#c8102e 66%)"></td></tr>
            <tr><td style="padding:28px">
                <h2 style="margin:0 0 16px;font-size:20px">{{ $subjectLine }}</h2>
                @foreach ($lines as $line)
                    <p style="line-height:1.6;margin:0 0 12px">{{ $line }}</p>
                @endforeach
                @if ($actionUrl)
                    <p style="margin:24px 0"><a href="{{ $actionUrl }}" style="background:#e0a526;color:#1c1c1c;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">{{ $actionText ?? 'Open' }}</a></p>
                @endif
            </td></tr>
            <tr><td style="padding:16px 28px;background:#f3f5f4;font-size:12px;color:#6c757d">
                {{ config('pearl360.name') }} · {{ setting('admin_email', config('pearl360.admin_email')) }} · +{{ ltrim(setting('admin_phone', config('pearl360.admin_phone')), '+') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
