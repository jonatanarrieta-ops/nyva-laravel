@csrf
<input type="hidden" name="establishment" value="{{ $establishment->id }}">

<div>
    <label for="question" class="nyva-label">Pregunta</label>
    <input id="question" name="question" type="text" value="{{ old('question', $faq?->question) }}" maxlength="255" placeholder="Ej. ¿Qué métodos de pago aceptan?" class="nyva-input">
    @error('question') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div>
    <label for="answer" class="nyva-label">Respuesta</label>
    <textarea id="answer" name="answer" rows="3" maxlength="2000" class="nyva-input">{{ old('answer', $faq?->answer) }}</textarea>
    @error('answer') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="status" value="0">
    <input type="checkbox" name="status" value="1" @checked(old('status', ($faq?->status ?? 'active') === 'active'))>
    Activa
</label>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('faqs.index', ['establishment' => $establishment->id]) }}" class="nyva-btn-secondary">Cancelar</a>
    <button type="submit" class="nyva-btn">Guardar</button>
</div>
