@csrf

<div>
    <label for="establishment_id" class="nyva-label">Establecimiento</label>
    <select id="establishment_id" name="establishment_id" class="nyva-input">
        <option value="">Selecciona</option>
        @foreach ($establishments as $establishment)
            <option value="{{ $establishment->id }}" @selected((string) old('establishment_id', $subscription?->establishment_id) === (string) $establishment->id)>{{ $establishment->name }}</option>
        @endforeach
    </select>
    @error('establishment_id') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="plan_id" class="nyva-label">Plan</label>
    <select id="plan_id" name="plan_id" class="nyva-input">
        <option value="">Selecciona</option>
        @foreach ($plans as $plan)
            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $subscription?->plan_id) === (string) $plan->id)>{{ $plan->name }} ({{ $plan->duration_days }} días)</option>
        @endforeach
    </select>
    @error('plan_id') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="billing_period" class="nyva-label">Periodicidad</label>
    <select id="billing_period" name="billing_period" class="nyva-input">
        <option value="monthly" @selected(old('billing_period', $subscription?->billing_period) !== 'annual')>Mensual</option>
        <option value="annual" @selected(old('billing_period', $subscription?->billing_period) === 'annual')>Anual</option>
    </select>
    @error('billing_period') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="status" class="nyva-label">Estado</label>
    <select id="status" name="status" class="nyva-input">
        @foreach (['active' => 'Activa', 'inactive' => 'Inactiva', 'cancelled' => 'Cancelada'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $subscription?->status ?? 'active') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('status') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="start_date" class="nyva-label">Inicio</label>
    <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $subscription?->start_date?->toDateString() ?? now()->toDateString()) }}" class="nyva-input">
    @error('start_date') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="end_date" class="nyva-label">Vencimiento</label>
    <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $subscription?->end_date?->toDateString()) }}" class="nyva-input">
    <p class="mt-1.5 text-xs text-slate-500">Si lo dejas vacío, se calcula con la duración del plan.</p>
    @error('end_date') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
    <a href="{{ route('admin.subscriptions.index') }}" class="nyva-btn-secondary">Cancelar</a>
    <button type="submit" class="nyva-btn">Guardar</button>
</div>
