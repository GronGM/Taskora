<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performer\UpdatePerformerProfileRequest;
use App\Http\Requests\Performer\UploadPerformerProfileImageRequest;
use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PerformerProfileController extends Controller
{
    public function show(): Response
    {
        $profile = $this->profileFor(request()->user());
        Gate::authorize('view', $profile);

        $profile->load(['specializations.parent']);

        return Inertia::render('Performer/Profile/Show', [
            'profile' => $this->profilePayload($profile),
            'categories' => $this->categoryOptions(),
            'statusLabels' => $this->statusLabels(),
            'requirements' => $this->verificationRequirements($profile),
        ]);
    }

    public function update(UpdatePerformerProfileRequest $request, NotificationService $notifications): RedirectResponse
    {
        $profile = $this->profileFor($request->user())->load('specializations');
        Gate::authorize('update', $profile);

        $before = $this->importantFingerprint($profile);
        $syncSpecializations = $request->has('specialization_ids');
        $specializationIds = $syncSpecializations
            ? $request->specializationIds()
            : $profile->specializations->pluck('id')->all();

        DB::transaction(function () use ($request, $profile, $notifications, $before, $syncSpecializations, $specializationIds): void {
            $data = $request->profileData();
            $after = $this->importantFingerprintFromData($profile, $data, $specializationIds);

            if ($profile->verification_status === PerformerProfile::STATUS_VERIFIED && $before !== $after) {
                $data['verification_status'] = PerformerProfile::STATUS_PENDING_REVIEW;
                $data['verification_note'] = null;
                $data['verified_at'] = null;
                $data['verified_by'] = null;
                $data['submitted_for_verification_at'] = now();
            }

            $profile->update($data);

            if ($syncSpecializations) {
                $profile->specializations()->sync($specializationIds);
            }

            if (($data['verification_status'] ?? null) === PerformerProfile::STATUS_PENDING_REVIEW) {
                $this->notifySubmitted($profile->fresh('user'), $notifications, $request->user());
            }
        });

        return redirect()
            ->route('performer.profile.show')
            ->with('success', 'Профиль исполнителя обновлен.');
    }

    public function uploadAvatar(UploadPerformerProfileImageRequest $request): RedirectResponse
    {
        $profile = $this->profileFor($request->user());
        Gate::authorize('update', $profile);

        $this->replacePublicFile($profile, 'avatar_path', $request->file('image')->store("performer-profiles/{$profile->id}", 'public'));

        return redirect()
            ->route('performer.profile.show')
            ->with('success', 'Аватар обновлен.');
    }

    public function uploadCover(UploadPerformerProfileImageRequest $request): RedirectResponse
    {
        $profile = $this->profileFor($request->user());
        Gate::authorize('update', $profile);

        $this->replacePublicFile($profile, 'cover_path', $request->file('image')->store("performer-profiles/{$profile->id}", 'public'));

        return redirect()
            ->route('performer.profile.show')
            ->with('success', 'Обложка обновлена.');
    }

    public function submitVerification(NotificationService $notifications): RedirectResponse
    {
        $profile = $this->profileFor(request()->user())->load(['specializations', 'user']);
        Gate::authorize('submitVerification', $profile);

        $errors = $this->verificationErrors($profile);

        if ($errors !== []) {
            return back()->withErrors($errors);
        }

        if ($profile->verification_status === PerformerProfile::STATUS_PENDING_REVIEW) {
            return redirect()
                ->route('performer.profile.show')
                ->with('success', 'Профиль уже находится на проверке.');
        }

        $profile->update([
            'verification_status' => PerformerProfile::STATUS_PENDING_REVIEW,
            'verification_note' => null,
            'verified_at' => null,
            'verified_by' => null,
            'submitted_for_verification_at' => now(),
        ]);

        $this->notifySubmitted($profile, $notifications, request()->user());

        return redirect()
            ->route('performer.profile.show')
            ->with('success', 'Профиль отправлен на проверку.');
    }

    private function profileFor(User $user): PerformerProfile
    {
        abort_unless($user->isPerformer(), 403);

        return PerformerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'verification_status' => PerformerProfile::STATUS_NOT_SUBMITTED,
                'is_public' => true,
            ],
        );
    }

    private function replacePublicFile(PerformerProfile $profile, string $field, string $path): void
    {
        $oldPath = $profile->{$field};

        $profile->update([$field => $path]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(PerformerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'display_name' => $profile->display_name,
            'headline' => $profile->headline,
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'response_time_label' => $profile->response_time_label,
            'portfolio_summary' => $profile->portfolio_summary,
            'avatar_url' => $profile->avatar_url,
            'cover_url' => $profile->cover_url,
            'verification_status' => $profile->verification_status,
            'verification_note' => $profile->verification_note,
            'verified_at' => $profile->verified_at?->format('d.m.Y'),
            'submitted_for_verification_at' => $profile->submitted_for_verification_at?->format('d.m.Y H:i'),
            'specialization_ids' => $profile->specializations->pluck('id')->values(),
            'specializations' => $profile->specializations->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? "{$category->parent->name} / {$category->name}" : $category->name,
            ]),
            'public_url' => route('performers.show', $profile->user),
            'update_url' => route('performer.profile.update'),
            'avatar_url_action' => route('performer.profile.avatar'),
            'cover_url_action' => route('performer.profile.cover'),
            'submit_verification_url' => route('performer.profile.submit-verification'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? "{$category->parent->name} / {$category->name}" : $category->name,
            ])
            ->all();
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

    /**
     * @return array<string, bool>
     */
    private function verificationRequirements(PerformerProfile $profile): array
    {
        return [
            'display_name' => filled($profile->display_name),
            'headline' => filled($profile->headline),
            'bio' => mb_strlen((string) $profile->bio) >= 100,
            'specializations' => $profile->specializations()->exists(),
            'proof' => $this->hasProofOfWork($profile),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function verificationErrors(PerformerProfile $profile): array
    {
        $errors = [];

        if (! filled($profile->display_name)) {
            $errors['display_name'] = 'Укажите публичное имя.';
        }

        if (! filled($profile->headline)) {
            $errors['headline'] = 'Укажите короткий заголовок профиля.';
        }

        if (mb_strlen((string) $profile->bio) < 100) {
            $errors['bio'] = 'Описание профиля должно быть не короче 100 символов.';
        }

        if (! $profile->specializations()->exists()) {
            $errors['specialization_ids'] = 'Выберите минимум одну специализацию.';
        }

        if (! $this->hasProofOfWork($profile)) {
            $errors['proof'] = 'Добавьте опубликованную работу портфолио или опубликованную услугу.';
        }

        return $errors;
    }

    private function hasProofOfWork(PerformerProfile $profile): bool
    {
        return $profile->publishedPortfolioItems()->exists()
            || $profile->user->services()->where('status', Service::STATUS_PUBLISHED)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function importantFingerprint(PerformerProfile $profile): array
    {
        return $this->importantFingerprintFromData(
            $profile,
            $profile->only(['headline', 'bio', 'portfolio_summary']),
            $profile->specializations->pluck('id')->values()->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $specializationIds
     * @return array<string, mixed>
     */
    private function importantFingerprintFromData(PerformerProfile $profile, array $data, array $specializationIds): array
    {
        sort($specializationIds);

        return [
            'headline' => (string) ($data['headline'] ?? $profile->headline),
            'bio' => (string) ($data['bio'] ?? $profile->bio),
            'portfolio_summary' => (string) ($data['portfolio_summary'] ?? $profile->portfolio_summary),
            'specialization_ids' => $specializationIds,
        ];
    }

    private function notifySubmitted(PerformerProfile $profile, NotificationService $notifications, User $actor): void
    {
        $profile->loadMissing('user');

        $notifications->notifyModeratorsAndAdmins(
            'performer_profile.submitted',
            'Профиль исполнителя на проверке',
            "Исполнитель {$profile->display_name} отправил профиль на ручную проверку.",
            route('moderator.performer-profiles.show', $profile),
            [
                'actor_id' => $actor->id,
                'icon' => 'profile',
                'severity' => 'info',
                'related_type' => PerformerProfile::class,
                'related_id' => $profile->id,
            ],
        );
    }
}
