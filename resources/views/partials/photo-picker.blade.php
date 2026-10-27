{{-- Profile picture upload with live preview. Needs $user (may be a new, unsaved user). --}}
<div class="photo-picker">
    <div class="preview" id="photo-preview">
        @if($user->exists)
            @include('partials.avatar', ['user' => $user, 'size' => 84, 'class' => 'avatar-ring'])
        @else
            <span class="avatar avatar-ring" style="width:84px;height:84px;font-size:2rem"><i class="bi bi-person"></i></span>
        @endif
    </div>
    <div class="flex-grow-1">
        <label class="form-label mb-1">Profile picture</label>
        <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="form-control" id="photo-input">
        <div class="form-text">JPG, PNG or WebP, up to 3 MB. A clear photo of your face — it appears on your library card.</div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('photo-input')?.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const img = document.createElement('img');
    img.className = 'avatar avatar-ring';
    img.style.cssText = 'width:84px;height:84px';
    img.src = URL.createObjectURL(file);
    document.getElementById('photo-preview').replaceChildren(img);
});
</script>
@endpush
