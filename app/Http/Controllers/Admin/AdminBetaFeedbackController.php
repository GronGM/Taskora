<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BetaFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminBetaFeedbackController extends Controller
{
    public function index(): Response
    {
        $feedback = BetaFeedback::query()
            ->with('user')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (BetaFeedback $item): array => $this->listPayload($item));

        $statusCounts = BetaFeedback::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return Inertia::render('Admin/BetaFeedback/Index', [
            'feedback' => $feedback,
            'summary' => [
                'total' => BetaFeedback::query()->count(),
                'open' => (int) ($statusCounts[BetaFeedback::STATUS_OPEN] ?? 0),
                'in_review' => (int) ($statusCounts[BetaFeedback::STATUS_IN_REVIEW] ?? 0),
                'resolved' => (int) ($statusCounts[BetaFeedback::STATUS_RESOLVED] ?? 0),
                'rejected' => (int) ($statusCounts[BetaFeedback::STATUS_REJECTED] ?? 0),
            ],
            'labels' => $this->labels(),
        ]);
    }

    public function show(BetaFeedback $feedback): Response
    {
        $feedback->load('user');

        return Inertia::render('Admin/BetaFeedback/Show', [
            'feedback' => $this->detailPayload($feedback),
            'statusOptions' => $this->optionPayload(BetaFeedback::statusLabels()),
            'labels' => $this->labels(),
        ]);
    }

    public function updateStatus(Request $request, BetaFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(BetaFeedback::statuses())],
        ]);

        $feedback->update(['status' => $validated['status']]);

        return redirect()
            ->route('admin.beta-feedback.show', $feedback)
            ->with('success', 'Статус beta-отзыва обновлен.');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function labels(): array
    {
        return [
            'types' => BetaFeedback::typeLabels(),
            'severities' => BetaFeedback::severityLabels(),
            'statuses' => BetaFeedback::statusLabels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(BetaFeedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'role' => $feedback->role,
            'type' => $feedback->type,
            'severity' => $feedback->severity,
            'status' => $feedback->status,
            'title' => $feedback->title,
            'scenario' => $feedback->scenario,
            'page_url' => $feedback->page_url,
            'user' => $feedback->user?->name,
            'created_at' => $feedback->created_at?->format('d.m.Y H:i'),
            'show_url' => route('admin.beta-feedback.show', $feedback),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(BetaFeedback $feedback): array
    {
        return [
            ...$this->listPayload($feedback),
            'description' => $feedback->description,
            'browser' => $feedback->browser,
            'screen_size' => $feedback->screen_size,
            'status_url' => route('admin.beta-feedback.status', $feedback),
            'user_email' => $feedback->user?->email,
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
