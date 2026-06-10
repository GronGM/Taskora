<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\ModerationFlag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ModerationFlagController extends Controller
{
    public function index(): Response
    {
        $flags = ModerationFlag::query()
            ->where('status', ModerationFlag::STATUS_OPEN)
            ->with('user')
            ->latest()
            ->get()
            ->map(fn (ModerationFlag $flag): array => [
                'id' => $flag->id,
                'reason' => $flag->reason,
                'matched_type' => $flag->matched_type,
                'matched_value' => $flag->matched_value,
                'user' => $flag->user?->name,
                'entity_type' => class_basename((string) $flag->entity_type),
                'entity_id' => $flag->entity_id,
                'created_at' => $flag->created_at?->format('d.m.Y H:i'),
                'resolve_url' => route('moderator.moderation-flags.resolve', $flag),
            ]);

        return Inertia::render('Moderator/ModerationFlags/Index', [
            'flags' => $flags,
        ]);
    }

    public function resolve(ModerationFlag $flag): RedirectResponse
    {
        $flag->update([
            'status' => ModerationFlag::STATUS_RESOLVED,
            'resolved_by' => request()->user()->id,
            'resolved_at' => now(),
        ]);

        return redirect()
            ->route('moderator.moderation-flags.index')
            ->with('success', 'Флаг отмечен обработанным.');
    }
}
