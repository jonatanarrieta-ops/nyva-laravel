@if (session('notice'))
    <x-ui.alert>{{ session('notice') }}</x-ui.alert>
@endif

@if (session('failure'))
    <x-ui.alert tone="error">{{ session('failure') }}</x-ui.alert>
@endif
