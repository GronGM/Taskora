<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performer\StoreServiceRequest;
use App\Http\Requests\Performer\SubmitServiceForReviewRequest;
use App\Http\Requests\Performer\UpdateServiceRequest;
use App\Models\Category;
use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PerformerServiceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Service::class);

        $services = request()->user()
            ->services()
            ->with('category')
            ->withCount('packages')
            ->latest()
            ->get()
            ->map(fn (Service $service): array => $this->serviceListPayload($service));

        return Inertia::render('Performer/Services/Index', [
            'services' => $services,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Service::class);

        return Inertia::render('Performer/Services/Create', [
            'categories' => $this->categoryOptions(),
            'defaultPackages' => [$this->defaultPackage()],
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = DB::transaction(function () use ($request): Service {
            $service = Service::create([
                ...$request->serviceData(),
                'user_id' => $request->user()->id,
                'slug' => $this->uniqueSlug($request->string('title')->toString()),
                'status' => $request->boolean('submit_for_review')
                    ? Service::STATUS_PENDING_REVIEW
                    : Service::STATUS_DRAFT,
            ]);

            $this->syncPackages($service, $request->packageData());

            return $service;
        });

        return redirect()
            ->route('performer.services.edit', $service)
            ->with('success', $service->status === Service::STATUS_PENDING_REVIEW
                ? 'Услуга сохранена и отправлена на модерацию.'
                : 'Черновик услуги сохранен.');
    }

    public function edit(Service $service): Response
    {
        Gate::authorize('view', $service);

        $service->load(['category', 'packages']);

        return Inertia::render('Performer/Services/Edit', [
            'service' => $this->serviceFormPayload($service),
            'categories' => $this->categoryOptions(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $importantChanges = $this->hasImportantChanges($service->load('packages'), $request->serviceData(), $request->packageData());

        DB::transaction(function () use ($request, $service, $importantChanges): void {
            $data = $request->serviceData();

            if ($service->title !== $data['title']) {
                $data['slug'] = $this->uniqueSlug($data['title'], $service);
            }

            if ($importantChanges && $service->status === Service::STATUS_PUBLISHED) {
                $data['status'] = Service::STATUS_PENDING_REVIEW;
                $data['rejection_reason'] = null;
                $data['moderated_by'] = null;
                $data['moderated_at'] = null;
            }

            $service->update($data);
            $this->syncPackages($service, $request->packageData());
        });

        return redirect()
            ->route('performer.services.edit', $service)
            ->with('success', 'Услуга обновлена.');
    }

    public function submitReview(SubmitServiceForReviewRequest $request, Service $service): RedirectResponse
    {
        $service->update([
            'status' => Service::STATUS_PENDING_REVIEW,
            'rejection_reason' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        return redirect()
            ->route('performer.services.index')
            ->with('success', 'Услуга отправлена на модерацию.');
    }

    public function archive(Service $service): RedirectResponse
    {
        Gate::authorize('archive', $service);

        $service->update(['status' => Service::STATUS_ARCHIVED]);

        return redirect()
            ->route('performer.services.index')
            ->with('success', 'Услуга перенесена в архив.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function syncPackages(Service $service, array $packages): void
    {
        $service->packages()->delete();

        foreach ($packages as $package) {
            $service->packages()->create($package);
        }
    }

    private function uniqueSlug(string $title, ?Service $except = null): string
    {
        $base = Str::slug($title, '-', 'ru') ?: 'service';
        $slug = $base;
        $counter = 2;

        while (Service::query()
            ->where('slug', $slug)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function hasImportantChanges(Service $service, array $data, array $packages): bool
    {
        foreach (['title', 'short_description', 'description', 'category_id'] as $field) {
            if ((string) $service->{$field} !== (string) ($data[$field] ?? '')) {
                return true;
            }
        }

        foreach (['price_from', 'delivery_days'] as $field) {
            if ((int) $service->{$field} !== (int) ($data[$field] ?? 0)) {
                return true;
            }
        }

        return $this->packageFingerprint($service->packages->all()) !== $this->packageFingerprint($packages);
    }

    /**
     * @param  array<int, ServicePackage|array<string, mixed>>  $packages
     * @return array<int, array<string, mixed>>
     */
    private function packageFingerprint(array $packages): array
    {
        return collect($packages)
            ->map(function (ServicePackage|array $package): array {
                $data = $package instanceof ServicePackage ? $package->only([
                    'name',
                    'description',
                    'price',
                    'delivery_days',
                    'revisions_count',
                ]) : $package;

                return [
                    'name' => (string) ($data['name'] ?? ''),
                    'description' => (string) ($data['description'] ?? ''),
                    'price' => (int) ($data['price'] ?? 0),
                    'delivery_days' => (int) ($data['delivery_days'] ?? 0),
                    'revisions_count' => (int) ($data['revisions_count'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function serviceListPayload(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'slug' => $service->slug,
            'status' => $service->status,
            'category' => $service->category?->name,
            'price_from' => $service->price_from,
            'delivery_days' => $service->delivery_days,
            'packages_count' => $service->packages_count,
            'rejection_reason' => $service->rejection_reason,
            'edit_url' => route('performer.services.edit', $service),
            'submit_review_url' => route('performer.services.submit-review', $service),
            'archive_url' => route('performer.services.archive', $service),
            'public_url' => $service->status === Service::STATUS_PUBLISHED ? $service->url : null,
        ];
    }

    private function serviceFormPayload(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'category_id' => $service->category_id,
            'short_description' => $service->short_description,
            'description' => $service->description,
            'price_from' => $service->price_from,
            'delivery_days' => $service->delivery_days,
            'status' => $service->status,
            'rejection_reason' => $service->rejection_reason,
            'is_locked' => $service->status === Service::STATUS_PENDING_REVIEW,
            'submit_review_url' => route('performer.services.submit-review', $service),
            'archive_url' => route('performer.services.archive', $service),
            'packages' => $service->packages->map(fn (ServicePackage $package): array => [
                'name' => $package->name,
                'description' => $package->description,
                'price' => $package->price,
                'delivery_days' => $package->delivery_days,
                'revisions_count' => $package->revisions_count,
            ]),
        ];
    }

    private function categoryOptions()
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
            ]);
    }

    private function defaultPackage(): array
    {
        return [
            'name' => 'Базовый',
            'description' => '',
            'price' => 1500,
            'delivery_days' => 3,
            'revisions_count' => 1,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            Service::STATUS_DRAFT => 'Черновик',
            Service::STATUS_PENDING_REVIEW => 'На модерации',
            Service::STATUS_PUBLISHED => 'Опубликована',
            Service::STATUS_REJECTED => 'Отклонена',
            Service::STATUS_ARCHIVED => 'В архиве',
        ];
    }
}
