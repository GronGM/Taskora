<?php

namespace App\Http\Controllers;

use App\Models\BetaFeedback;
use App\Support\BetaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BetaFeedbackController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless(BetaAccess::betaToolingAvailable(), 404);

        return Inertia::render('BetaFeedback/Create', [
            'roleOptions' => $this->optionPayload($this->roleLabels()),
            'typeOptions' => $this->optionPayload(BetaFeedback::typeLabels()),
            'severityOptions' => $this->optionPayload(BetaFeedback::severityLabels()),
            'storeUrl' => route('beta-feedback.store'),
            'defaultPageUrl' => $request->headers->get('referer'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(BetaAccess::betaToolingAvailable(), 404);

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:50'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'scenario' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(BetaFeedback::types())],
            'severity' => ['required', 'string', Rule::in(BetaFeedback::severities())],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'browser' => ['nullable', 'string', 'max:512'],
            'screen_size' => ['nullable', 'string', 'max:50'],
        ]);

        BetaFeedback::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'status' => BetaFeedback::STATUS_OPEN,
        ]);

        return redirect()
            ->route('beta-feedback.create')
            ->with('success', 'Спасибо, обращение сохранено. Мы разберем его в beta-очереди.');
    }

    /**
     * @return array<string, string>
     */
    private function roleLabels(): array
    {
        return [
            'guest' => 'Гость',
            'customer' => 'Заказчик',
            'performer' => 'Исполнитель',
            'moderator' => 'Модератор',
            'admin' => 'Администратор',
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<int, array{value: string, label: string}>
     */
    private function optionPayload(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }
}
