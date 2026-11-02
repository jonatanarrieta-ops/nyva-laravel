@if (session('notice'))
    <div class="nyva-alert-success" role="status">{{ session('notice') }}</div>
@endif

@if (session('failure'))
    <div class="nyva-alert-error" role="alert">{{ session('failure') }}</div>
@endif
