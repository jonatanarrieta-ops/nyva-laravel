<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesCurrentEstablishment;
use App\Models\FrequentlyAskedQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FaqController extends Controller
{
    use ResolvesCurrentEstablishment;

    public function index(Request $request): View
    {
        $establishment = $this->currentEstablishment($request, 'settings');

        return view('faqs.index', [
            'title' => 'Preguntas frecuentes',
            'establishment' => $establishment,
            'establishments' => $this->establishmentOptions($request, 'settings'),
            'faqs' => $establishment->frequentlyAskedQuestions()->orderBy('question')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('faqs.create', [
            'title' => 'Nueva pregunta',
            'establishment' => $this->currentEstablishment($request, 'settings'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $establishment = $this->currentEstablishment($request, 'settings');

        Gate::authorize('update', $establishment);

        $establishment->frequentlyAskedQuestions()->create($this->validated($request));

        return redirect()
            ->route('faqs.index', ['establishment' => $establishment->id])
            ->with('success', 'Pregunta guardada.');
    }

    public function edit(Request $request, string $id): View
    {
        $faq = $this->ownedFaqs($request)->with('establishment')->findOrFail($id);

        return view('faqs.edit', [
            'title' => 'Editar pregunta',
            'faq' => $faq,
            'establishment' => $faq->establishment,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $faq = $this->ownedFaqs($request)->findOrFail($id);

        $faq->update($this->validated($request));

        return redirect()
            ->route('faqs.index', ['establishment' => $faq->establishment_id])
            ->with('success', 'Pregunta guardada.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $faq = $this->ownedFaqs($request)->findOrFail($id);
        $establishmentId = $faq->establishment_id;

        $faq->delete();

        return redirect()
            ->route('faqs.index', ['establishment' => $establishmentId])
            ->with('success', 'Pregunta eliminada.');
    }

    /**
     * @return array{question: string, answer: string, status: string}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'status' => ['nullable', 'boolean'],
        ], [
            'question.required' => 'La pregunta es obligatoria.',
            'answer.required' => 'La respuesta es obligatoria.',
        ]);

        return [
            'question' => trim($data['question']),
            'answer' => trim($data['answer']),
            'status' => $request->boolean('status') ? 'active' : 'inactive',
        ];
    }

    /**
     * @return Builder<FrequentlyAskedQuestion>
     */
    private function ownedFaqs(Request $request): Builder
    {
        return FrequentlyAskedQuestion::query()
            ->whereIn('establishment_id', $request->user()->accessibleEstablishments('settings')->select('id'));
    }
}
