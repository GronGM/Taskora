<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderator\RejectPerformerProfileRequest;
use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\Review;
use App\Models\Service;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModeratorPerformerProfileController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('reviewAny', PerformerProfile::class);

        $status = $request->string('status')->toString() ?: PerformerProfile::STATUS_PENDING_REVIEW;
        $status = in_array($status, [
            PerformerProfile::STATUS_PENDING_REVIEW,
            PerformerProfile::STATUS_VERIFIED,
            PerformerProfile::STATUS_REJECTED,
        ], true) ? $status : PerformerProfile::STATUS_PENDING_REVIEW;

        $profiles = PerformerProfile::query()
            ->where('verification_status', $status)
            ->with(['user', 'specializations'])
            ->latest('submitted_for_verification_at')
            ->latest()
            ->get()
            ->map(fn (PerformerProfile $profile): array => [
                'id' => $profile->id,
                'display_name' => $profile->display_name ?: $profile->user->name,
                'headline' => $profile->headline,
                'status' => $profile->verification_status,
                'submitted_at' => $profile->submitted_for_verification_at?->format('d.m.Y H:i'),
                'specializations' => $profile->specializations->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ]),
                'review_url' => route('moderator.performer-profiles.show', $profile),
            ]);

        return Inertia::render('Moderator/PerformerProfiles/Index', [
            'profiles' => $profiles,
            'filters' => ['status' => $status],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function show(PerformerProfile $profile): Response
    {
        Gate::authorize('review', $profile);

        $profile->load([
            'user.services' => fn ($query) => $query->where('status', Service::STATUS_PUBLISHED)->with('category'),
            'specializations.parent',
            'portfolioItems.category',
        ]);

        $reviews = $profile->user->receivedReviews()
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->with(['customer', 'service', 'task', 'order'])
            ->latest('published_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Review $review): array => $this->reviewPayload($review));

        return Inertia::render('Moderator/PerformerProfiles/Show', [
            'profile' => $this->profilePayload($profile),
            'reviews' => $reviews,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function approve(PerformerProfile $profile, NotificationService $notifications): RedirectResponse
    {
        Gate::authorize('approve', $profile);

        $profile->update([
            'verification_status' => PerformerProfile::STATUS_VERIFIED,
            'verification_note' => null,
            'verified_at' => now(),
            'verified_by' => request()->user()->id,
            'published_at' => $profile->published_at ?? now(),
        ]);
        $profile->load('user');

        $notifications->notifyUser(
            $profile->user,
            'performer_profile.approved',
            'Профиль подтвержден',
            'Модератор подтвердил ваш профиль исполнителя. Бейдж проверки теперь виден в каталоге.',
            route('performer.profile.show'),
            [
                'actor_id' => request()->user()->id,
                'icon' => 'profile',
                'severity' => 'success',
                'related_type' => PerformerProfile::class,
                'related_id' => $profile->id,
            ],
        );

        return redirect()
            ->route('moderator.performer-profiles.index')
            ->with('success', 'Профиль подтвержден.');
    }

    public function reject(RejectPerformerProfileRequest $request, PerformerProfile $profile, NotificationService $notifications): RedirectResponse
    {
        $reason = $request->validated('reason');

        $profile->update([
            'verification_status' => PerformerProfile::STATUS_REJECTED,
            'verification_note' => $reason,
            'verified_at' => null,
            'verified_by' => $request->user()->id,
        ]);
        $profile->load('user');

        $notifications->notifyUser(
            $profile->user,
            'performer_profile.rejected',
            'Профиль отклонен',
            "Модератор отклонил профиль исполнителя. Причина: {$reason}",
            route('performer.profile.show'),
            [
                'actor_id' => $request->user()->id,
                'icon' => 'profile',
                'severity' => 'warning',
                'related_type' => PerformerProfile::class,
                'related_id' => $profile->id,
            ],
        );

        return redirect()
            ->route('moderator.performer-profiles.index')
            ->with('success', 'Профиль отклонен.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(PerformerProfile $profile): array
    {
        $user = $profile->user;

        return [
            'id' => $profile->id,
            'display_name' => $profile->display_name ?: $user->name,
            'headline' => $profile->headline,
            'bio' => $profile->bio,
            'portfolio_summary' => $profile->portfolio_summary,
            'avatar_url' => $profile->avatar_url,
            'cover_url' => $profile->cover_url,
            'verification_status' => $profile->verification_status,
            'verification_note' => $profile->verification_note,
            'submitted_at' => $profile->submitted_for_verification_at?->format('d.m.Y H:i'),
            'verified_at' => $profile->verified_at?->format('d.m.Y H:i'),
            'performer' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rating' => $user->performer_reviews_count > 0 ? (float) $user->performer_rating : null,
                'reviews_count' => $user->performer_reviews_count,
                'completed_orders_count' => $user->performer_completed_orders_count,
                'published_services_count' => $user->services->count(),
            ],
            'specializations' => $profile->specializations->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? "{$category->parent->name} / {$category->name}" : $category->name,
            ]),
            'portfolio' => $profile->portfolioItems->map(fn (PerformerPortfolioItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'status' => $item->status,
                'is_public' => $item->is_public,
                'category' => $item->category?->name,
                'image_url' => $item->image_url,
                'file_url' => $item->file_url,
                'external_url' => $item->external_url,
            ]),
            'services' => $user->services->map(fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'category' => $service->category?->name,
                'price_from' => $service->price_from,
                'delivery_days' => $service->delivery_days,
                'url' => $service->url,
            ]),
            'approve_url' => route('moderator.performer-profiles.approve', $profile),
            'reject_url' => route('moderator.performer-profiles.reject', $profile),
            'public_url' => route('performers.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'published_at' => $review->published_at?->format('d.m.Y'),
            'customer' => ['name' => $review->customer?->name ?? 'Заказчик Таскоры'],
            'source' => [
                'title' => $review->service?->title ?? $review->task?->title ?? $review->order?->title,
                'url' => $review->service?->url,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            PerformerProfile::STATUS_NOT_SUBMITTED => 'Не отправлен',
            PerformerProfile::STATUS_PENDING_REVIEW => 'На проверке',
            PerformerProfile::STATUS_VERIFIED => 'Проверен',
            PerformerProfile::STATUS_REJECTED => 'Отклонен',
        ];
    }
}
