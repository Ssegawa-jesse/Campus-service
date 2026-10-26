@extends('layouts.app')
@section('title', 'Issue / Return')
@section('sidebar') @include('partials.admin-nav') @endsection
@section('content')
<h3 class="mb-3"><i class="bi bi-qr-code-scan"></i> Issue &amp; return books</h3>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Camera scanner</strong>
                <div>
                    <button type="button" id="start-scan" class="btn btn-sm btn-lib"><i class="bi bi-camera"></i> Start</button>
                    <button type="button" id="stop-scan" class="btn btn-sm btn-outline-secondary d-none">Stop</button>
                </div>
            </div>
            <div class="card-body">
                <div id="reader" class="mb-2" style="width:100%"></div>
                <p class="small text-muted mb-1">Scanning fills the <strong>highlighted field</strong>. Click a field to choose where the next scan goes. A USB barcode/QR scanner that types into the field also works.</p>
                <div id="preview" class="small"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#issue-tab" type="button">Issue (borrow)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#return-tab" type="button">Return</button></li>
        </ul>
        <div class="tab-content card card-body border-top-0 shadow-sm">
            <div class="tab-pane fade show active" id="issue-tab">
                <form method="POST" action="{{ route('admin.circulation.issue') }}">@csrf
                    <div class="mb-3"><label class="form-label">1. Member number (scan library card)</label>
                        <input name="member_no" id="member_no" value="{{ old('member_no') }}" class="form-control form-control-lg scan-target" autocomplete="off" required></div>
                    <div class="mb-3"><label class="form-label">2. Book copy code (scan book label)</label>
                        <input name="copy_code" id="issue_copy" value="{{ old('copy_code') }}" class="form-control form-control-lg scan-target" autocomplete="off" required></div>
                    <button class="btn btn-success btn-lg w-100"><i class="bi bi-box-arrow-right"></i> Issue book</button>
                </form>
            </div>
            <div class="tab-pane fade" id="return-tab">
                <form method="POST" action="{{ route('admin.circulation.return') }}">@csrf
                    <div class="mb-3"><label class="form-label">Book copy code (scan book label)</label>
                        <input name="copy_code" id="return_copy" class="form-control form-control-lg scan-target" autocomplete="off" required></div>
                    <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-left"></i> Receive book</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Fines are calculated automatically. If someone reserved this title, the copy is put on hold and they get an SMS.</p>
            </div>
        </div>
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white"><strong>Recent activity</strong></div>
            <table class="table table-sm mb-0">
                <tbody>
                @forelse($recent as $l)
                    <tr><td>{{ $l->member->user->name }}</td><td>{{ Str::limit($l->copy->book->title, 40) }}</td>
                        <td class="text-end">{!! $l->returned_at ? '<span class="badge bg-success">Returned '.$l->returned_at->format('d M').'</span>' : '<span class="badge bg-warning text-dark">Due '.$l->due_at->format('d M').'</span>' !!}</td></tr>
                @empty
                    <tr><td class="text-muted text-center">No activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
<script>
(() => {
    const lookupUrl = @json(route('admin.circulation.lookup'));
    let target = document.getElementById('member_no');
    const highlight = el => {
        document.querySelectorAll('.scan-target').forEach(i => i.classList.remove('border-primary', 'border-3'));
        el.classList.add('border-primary', 'border-3');
        target = el;
    };
    document.querySelectorAll('.scan-target').forEach(el => {
        el.addEventListener('focus', () => highlight(el));
        el.addEventListener('change', () => preview(el.value));
    });
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => tab.addEventListener('shown.bs.tab', e => {
        highlight(document.getElementById(e.target.dataset.bsTarget === '#return-tab' ? 'return_copy' : 'member_no'));
    }));
    highlight(target);

    async function preview(code) {
        const box = document.getElementById('preview');
        if (!code) { box.innerHTML = ''; return; }
        const res = await fetch(lookupUrl + '?code=' + encodeURIComponent(code), {headers: {'Accept': 'application/json'}});
        const d = await res.json();
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));
        if (d.kind === 'member') {
            box.innerHTML = `<div class="alert ${d.blocks.length ? 'alert-warning' : 'alert-success'} mb-0"><strong>${esc(d.name)}</strong> (${esc(d.member_no)}, ${esc(d.type)})<br>
                Loans ${esc(d.loans)} · Unpaid fines UGX ${Number(d.fines).toLocaleString()}${d.blocks.length ? '<br>' + d.blocks.map(esc).join('<br>') : ''}</div>`;
        } else if (d.kind === 'copy') {
            box.innerHTML = `<div class="alert alert-info mb-0"><strong>${esc(d.title)}</strong> (${esc(d.code)})<br>Status: ${esc(d.status.replace('_', ' '))}
                ${d.borrower ? '<br>Borrowed by ' + esc(d.borrower) + ', due ' + esc(d.due) : ''}</div>`;
        } else {
            box.innerHTML = '<div class="alert alert-danger mb-0">Code not recognised.</div>';
        }
    }

    let scanner = null, last = '', lastAt = 0;
    document.getElementById('start-scan').addEventListener('click', async () => {
        scanner = new Html5Qrcode('reader');
        try {
            await scanner.start({facingMode: 'environment'}, {fps: 10, qrbox: 220}, text => {
                if (text === last && Date.now() - lastAt < 3000) return;
                last = text; lastAt = Date.now();
                target.value = text;
                preview(text);
                if (target.id === 'member_no') highlight(document.getElementById('issue_copy'));
            });
            document.getElementById('start-scan').classList.add('d-none');
            document.getElementById('stop-scan').classList.remove('d-none');
        } catch (e) {
            document.getElementById('preview').innerHTML = '<div class="alert alert-danger mb-0">Could not open the camera: ' + e + '</div>';
        }
    });
    document.getElementById('stop-scan').addEventListener('click', async () => {
        if (scanner) await scanner.stop();
        document.getElementById('start-scan').classList.remove('d-none');
        document.getElementById('stop-scan').classList.add('d-none');
    });
})();
</script>
@endpush
