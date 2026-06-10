<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderator\RejectServiceRequest;
use App\Models\ModerationFlag;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModeratorServiceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('reviewAny', Service::class);

        $services = Service::query()
            ->where('status', Service::STATUS_PENDING_REVIEW)
            ->with(['category', 'user'])
            ->withCount('packages')
            ->latest('updated_at')
            ->get()
            ->map(fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'performer' => $service->user->name,
                'category' => $service->category->name,
                'price_from' => $service->price_from,
                'delivery_days' => $service->delivery_days,
                'packages_count' => $service->packages_count,
                'submitted_at' => $service->updated_at?->format('d.m.Y H:i'),
                'review_url' => route('moderator.services.show', $service),
            ]);

        return Inertia::render('Moderator/Services/Index', [
            'services' => $services,
        ]);
    }

    public function show(Service $service): Response
    {
        Gate::authorize('review', $service);

        $service->load(['category', 'user', 'packages']);

        return Inertia::render('Moderator/Services/Show', [
            'service' => [
                'id' => $service->id,
                'title' => $service->title,
                'short_description' => $service->short_description,
                'description' => $service->description,
                'price_from' => $service->price_from,
                'delivery_days' => $service->delivery_days,
                'submitted_at' => $service->updated_at?->format('d.m.Y H:i'),
                'category' => [
                    'name' => $service->category->name,
                ],
                'performer' => [
                    'name' => $service->user->name,
                    'email' => $service->user->email,
                ],
                'packages' => $service->packages->map(fn (ServicePackage $package): array => [
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'delivery_days' => $package->delivery_days,
                    'revisions_count' => $package->revisions_count,
                ]),
                'approve_url' => route('moderator.services.approve', $service),
                'reject_url' => route('moderator.services.reject', $service),
            ],
            'flags' => $this->serviceFlags($service),
        ]);
    }

    public function approve(Service $service, NotificationService $notifications): RedirectResponse
    {
        Gate::authorize('approve', $service);

        $service->update([
            'status' => Service::STATUS_PUBLISHED,
            'rejection_reason' => null,
            'moderated_by' => request()->user()->id,
            'moderated_at' => now(),
        ]);
        $service->load('user');

        $notifications->notifyUser(
            $service->user,
            'service.approved',
            'Услуга одобрена',
            "Модератор одобрил услугу «{$service->title}». Она опубликована в каталоге.",
            route('services.show', ['service' => $service->slug]),
            [
                'actor_id' => request()->user()->id,
                'icon' => 'service',
                'severity' => 'success',
                'related_type' => Service::class,
                'related_id' => $service->id,
            ],
        );

        return redirect()
            ->route('moderator.services.index')
            ->with('success', 'Услуга опубликована.');
    }

    public function reject(RejectServiceRequest $request, Service $service, NotificationService $notifications): RedirectResponse
    {
        $service->update([
            'status' => Service::STATUS_REJECTED,
            'rejection_reason' => $request->validated('reason'),
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);
        $service->load('user');

        $notifications->notifyUser(
            $service->user,
            'service.rejected',
            'Услуга отклонена',
            "Модератор отклонил услугу «{$service->title}». Проверьте комментарий и отправьте услугу повторно.",
            route('performer.services.edit', $service),
            [
                'actor_id' => $request->user()->id,
                'icon' => 'service',
                'severity' => 'warning',
                'related_type' => Service::class,
                'related_id' => $service->id,
            ],
        );

        return redirect()
            ->route('moderator.services.index')
            ->with('success', 'Услуга отклонена.');
    }

    private function serviceFlags(Service $service)
    {
        return ModerationFlag::query()
            ->where('entity_type', Service::class)
            ->where('entity_id', $service->id)
            ->latest()
            ->get()
            ->map(fn (ModerationFlag $flag): array => [
                'id' => $flag->id,
                'reason' => $flag->reason,
                'matched_type' => $flag->matched_type,
                'matched_value' => $flag->matched_value,
                'status' => $flag->status,
                'created_at' => $flag->created_at?->format('d.m.Y H:i'),
            ]);
    }
}
