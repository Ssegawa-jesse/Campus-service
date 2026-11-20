<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pearl360 – setup not finished</title>
<style>
    body { margin: 0; font-family: Segoe UI, Arial, sans-serif; background: #faf6ee; color: #1c2321; }
    .box { max-width: 720px; margin: 60px auto; background: #fff; border-radius: 16px; padding: 32px 36px; box-shadow: 0 10px 30px rgba(0,0,0,.08); border-top: 6px solid #e0a526; }
    h1 { color: #1f4d3a; margin-top: 0; font-size: 26px; }
    code, pre { background: #143326; color: #e8f1ec; border-radius: 8px; }
    pre { padding: 14px 16px; overflow-x: auto; font-size: 14px; line-height: 1.6; }
    code { padding: 2px 6px; }
    .why { background: #fff7e3; border-radius: 10px; padding: 12px 16px; }
    li { margin-bottom: 6px; }
</style>
</head>
<body>
<div class="box">
    <h1>Pearl360 is installed, but setup isn't finished</h1>
    <p class="why"><strong>Problem:</strong> {{ $problem }}</p>
    <p><strong>Easiest fix (Windows):</strong> close this server window, then double-click <code>setup.bat</code> in the project folder.</p>
    <p><strong>Or run these commands</strong> in the project folder:</p>
    <pre>{{ $commands }}</pre>
    <p>Then refresh this page. If it still fails, run <code>php artisan pearl360:doctor</code>. It lists exactly what is wrong.</p>
</div>
</body>
</html>
