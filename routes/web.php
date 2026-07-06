<?php

use App\Http\Controllers\Admin\AdminBetaFeedbackController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\AdminMailSettingsController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentSettingsController;
use App\Http\Controllers\Admin\AdminTaskTypeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\BetaAccessController;
use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerReviewController;
use App\Http\Controllers\Customer\CustomerTaskController;
use App\Http\Controllers\Customer\CustomerTaskOfferAcceptController;
use App\Http\Controllers\Customer\CustomerTaskOfferController;
use App\Http\Controllers\Dashboard\DashboardRedirectController;
use App\Http\Controllers\Dashboard\RoleDashboardController;
use App\Http\Controllers\Messages\MessageController;
use App\Http\Controllers\Moderator\ModerationFlagController;
use App\Http\Controllers\Moderator\ModeratorDisputeController;
use App\Http\Controllers\Moderator\ModeratorPerformerProfileController;
use App\Http\Controllers\Moderator\ModeratorServiceController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Order\DisputeController;
use App\Http\Controllers\Order\DisputeMessageController;
use App\Http\Controllers\Order\OrderFileController;
use App\Http\Controllers\Order\OrderMessageController;
use App\Http\Controllers\Order\OrderWorkspaceController;
use App\Http\Controllers\Performer\BulkTaskTypeFavoriteController;
use App\Http\Controllers\Performer\CategoryFavoriteController;
use App\Http\Controllers\Performer\PerformerFinanceController;
use App\Http\Controllers\Performer\PerformerFavoriteController;
use App\Http\Controllers\Performer\PerformerOrderController;
use App\Http\Controllers\Performer\PerformerPortfolioController;
use App\Http\Controllers\Performer\PerformerProfileController;
use App\Http\Controllers\Performer\PerformerServiceController;
use App\Http\Controllers\Performer\TaskFavoriteController;
use App\Http\Controllers\Performer\TaskOfferController;
use App\Http\Controllers\Performer\TaskTypeFavoriteController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\BetaTestingController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ServiceOrderController;
use App\Http\Controllers\Public\TaskBoardController;
use App\Support\BetaAccess;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/robots.txt', fn () => Response::make(
    BetaAccess::shouldNoIndex()
        ? "User-agent: *\nDisallow: /\n"
        : "User-agent: *\nAllow: /\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');

Route::get('/beta-access', [BetaAccessController::class, 'show'])->name('beta-access.show');
Route::post('/beta-access', [BetaAccessController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('beta-access.store');

Route::get('/beta-testing', BetaTestingController::class)->name('beta-testing');
Route::get('/beta-feedback/create', [BetaFeedbackController::class, 'create'])->name('beta-feedback.create');
Route::post('/beta-feedback', [BetaFeedbackController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('beta-feedback.store');

Route::get('/', HomeController::class)->name('home');

Route::get('/r/{code}', [\App\Http\Controllers\Referrals\ReferralController::class, 'redirect'])->name('referral.redirect');
Route::post('/webhooks/yookassa', \App\Http\Controllers\Webhooks\YooKassaWebhookController::class)->name('webhooks.yookassa');
Route::get('/legal/offer', [\App\Http\Controllers\Public\LegalController::class, 'offer'])->name('legal.offer');
Route::get('/legal/safe-deal', [\App\Http\Controllers\Public\LegalController::class, 'safeDeal'])->name('legal.safe-deal');
Route::get('/legal/payments', [\App\Http\Controllers\Public\LegalController::class, 'payments'])->name('legal.payments');
Route::get('/legal/requisites', [\App\Http\Controllers\Public\LegalController::class, 'requisites'])->name('legal.requisites');
Route::get('/legal/privacy', [\App\Http\Controllers\Public\LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/how-it-works', [\App\Http\Controllers\Public\HelpController::class, 'howItWorks'])->name('help.how-it-works');
Route::get('/faq', [\App\Http\Controllers\Public\HelpController::class, 'faq'])->name('help.faq');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');
Route::get('/services/{service:slug}', [CatalogController::class, 'service'])->name('services.show');

Route::get('/tasks', [TaskBoardController::class, 'index'])->name('tasks');
Route::get('/tasks/{task:slug}', [TaskBoardController::class, 'show'])->name('tasks.show');

Route::get('/performers', [CatalogController::class, 'performers'])->name('performers');
Route::get('/performers/{user}/reviews', [CatalogController::class, 'performerReviews'])->name('performers.reviews');
Route::get('/performers/{user}', [CatalogController::class, 'performer'])->name('performers.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:taskora-password-email')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:taskora-password-reset')
        ->name('password.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('/settings', [\App\Http\Controllers\Settings\AccountSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [\App\Http\Controllers\Settings\AccountSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/avatar', [\App\Http\Controllers\Settings\AccountSettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::patch('/settings/password', [\App\Http\Controllers\Settings\AccountSettingsController::class, 'updatePassword'])->name('settings.password');
    Route::get('/referrals', [\App\Http\Controllers\Referrals\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('throttle:taskora-notifications')
        ->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->middleware('throttle:taskora-notifications')
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->middleware('throttle:taskora-notifications')
        ->name('notifications.read-all');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/orders/{order}', [MessageController::class, 'showOrder'])->name('messages.orders.show');
    Route::post('/messages/orders/{order}', [MessageController::class, 'storeOrder'])
        ->middleware('throttle:taskora-order-messages')
        ->name('messages.orders.store');
    Route::post('/messages/orders/{order}/mark-read', [MessageController::class, 'markOrderRead'])
        ->middleware('throttle:taskora-order-messages')
        ->name('messages.orders.mark-read');
    Route::get('/messages/disputes/{dispute}', [MessageController::class, 'showDispute'])->name('messages.disputes.show');
    Route::post('/messages/disputes/{dispute}', [MessageController::class, 'storeDispute'])
        ->middleware('throttle:taskora-order-messages')
        ->name('messages.disputes.store');
    Route::post('/messages/disputes/{dispute}/mark-read', [MessageController::class, 'markDisputeRead'])
        ->middleware('throttle:taskora-order-messages')
        ->name('messages.disputes.mark-read');

    Route::get('/customer/dashboard', [RoleDashboardController::class, 'customer'])
        ->middleware('role:customer')
        ->name('customer.dashboard');

    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function (): void {
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/review/create', [CustomerReviewController::class, 'create'])->name('orders.review.create');
        Route::post('/orders/{order}/review', [CustomerReviewController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('orders.review.store');
        Route::get('/orders/{order}/workspace', OrderWorkspaceController::class)->name('orders.workspace');
        Route::get('/orders/{order}/disputes/create', [DisputeController::class, 'create'])->name('orders.disputes.create');
        Route::post('/orders/{order}/disputes', [DisputeController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('orders.disputes.store');
        Route::post('/orders/{order}/mark-paid', [CustomerOrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('/orders/{order}/request-revision', [CustomerOrderController::class, 'requestRevision'])->name('orders.request-revision');
        Route::post('/orders/{order}/complete', [CustomerOrderController::class, 'complete'])->name('orders.complete');
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/messages', [OrderMessageController::class, 'store'])
            ->middleware('throttle:taskora-order-messages')
            ->name('orders.messages.store');
        Route::post('/orders/{order}/files', [OrderFileController::class, 'store'])
            ->middleware('throttle:taskora-order-files')
            ->name('orders.files.store');
        Route::get('/orders/{order}/files/{file}/download', [OrderFileController::class, 'download'])->name('orders.files.download');
        Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::post('/disputes/{dispute}/messages', [DisputeMessageController::class, 'store'])
            ->middleware('throttle:taskora-order-messages')
            ->name('disputes.messages.store');
        Route::get('/reviews', [CustomerReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{review}', [CustomerReviewController::class, 'show'])->name('reviews.show');
        Route::get('/tasks', [CustomerTaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/create', [CustomerTaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [CustomerTaskController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('tasks.store');
        Route::get('/tasks/{task}', [CustomerTaskController::class, 'show'])->name('tasks.show');
        Route::get('/tasks/{task}/edit', [CustomerTaskController::class, 'edit'])->name('tasks.edit');
        Route::match(['put', 'patch'], '/tasks/{task}', [CustomerTaskController::class, 'update'])->name('tasks.update');
        Route::post('/tasks/{task}/publish', [CustomerTaskController::class, 'publish'])->name('tasks.publish');
        Route::post('/tasks/{task}/archive', [CustomerTaskController::class, 'archive'])->name('tasks.archive');
        Route::post('/task-offers/{offer}/accept', CustomerTaskOfferAcceptController::class)->name('task-offers.accept');
        Route::post('/task-offers/{offer}/reject', [CustomerTaskOfferController::class, 'reject'])->name('task-offers.reject');
    });

    Route::get('/performer/dashboard', [RoleDashboardController::class, 'performer'])
        ->middleware('role:performer')
        ->name('performer.dashboard');

    Route::middleware('role:performer')->prefix('performer')->name('performer.')->group(function (): void {
        Route::get('/finance', PerformerFinanceController::class)->name('finance.index');
        Route::get('/favorites', PerformerFavoriteController::class)->name('favorites.index');
        Route::get('/profile', [PerformerProfileController::class, 'show'])->name('profile.show');
        Route::patch('/profile', [PerformerProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/avatar', [PerformerProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::post('/profile/cover', [PerformerProfileController::class, 'uploadCover'])->name('profile.cover');
        Route::post('/profile/submit-verification', [PerformerProfileController::class, 'submitVerification'])
            ->middleware('throttle:taskora-create')
            ->name('profile.submit-verification');
        Route::get('/portfolio', [PerformerPortfolioController::class, 'index'])->name('portfolio.index');
        Route::get('/portfolio/create', [PerformerPortfolioController::class, 'create'])->name('portfolio.create');
        Route::post('/portfolio', [PerformerPortfolioController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('portfolio.store');
        Route::get('/portfolio/{item}/edit', [PerformerPortfolioController::class, 'edit'])->name('portfolio.edit');
        Route::patch('/portfolio/{item}', [PerformerPortfolioController::class, 'update'])->name('portfolio.update');
        Route::post('/portfolio/{item}/hide', [PerformerPortfolioController::class, 'hide'])->name('portfolio.hide');
        Route::post('/portfolio/{item}/publish', [PerformerPortfolioController::class, 'publish'])->name('portfolio.publish');
        Route::delete('/portfolio/{item}', [PerformerPortfolioController::class, 'destroy'])->name('portfolio.destroy');
        Route::get('/orders', [PerformerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [PerformerOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/workspace', OrderWorkspaceController::class)->name('orders.workspace');
        Route::get('/orders/{order}/disputes/create', [DisputeController::class, 'create'])->name('orders.disputes.create');
        Route::post('/orders/{order}/disputes', [DisputeController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('orders.disputes.store');
        Route::post('/orders/{order}/submit-work', [PerformerOrderController::class, 'submitWork'])->name('orders.submit-work');
        Route::post('/orders/{order}/cancel', [PerformerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/messages', [OrderMessageController::class, 'store'])
            ->middleware('throttle:taskora-order-messages')
            ->name('orders.messages.store');
        Route::post('/orders/{order}/files', [OrderFileController::class, 'store'])
            ->middleware('throttle:taskora-order-files')
            ->name('orders.files.store');
        Route::get('/orders/{order}/files/{file}/download', [OrderFileController::class, 'download'])->name('orders.files.download');
        Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::post('/disputes/{dispute}/messages', [DisputeMessageController::class, 'store'])
            ->middleware('throttle:taskora-order-messages')
            ->name('disputes.messages.store');
        Route::get('/services', [PerformerServiceController::class, 'index'])->name('services.index');
        Route::get('/services/create', [PerformerServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [PerformerServiceController::class, 'store'])
            ->middleware('throttle:taskora-create')
            ->name('services.store');
        Route::get('/services/{service}/edit', [PerformerServiceController::class, 'edit'])->name('services.edit');
        Route::match(['put', 'patch'], '/services/{service}', [PerformerServiceController::class, 'update'])->name('services.update');
        Route::post('/services/{service}/submit-review', [PerformerServiceController::class, 'submitReview'])
            ->middleware('throttle:taskora-create')
            ->name('services.submit-review');
        Route::post('/services/{service}/archive', [PerformerServiceController::class, 'archive'])->name('services.archive');
        Route::get('/offers', [TaskOfferController::class, 'index'])->name('offers.index');
        Route::post('/task-offers/{offer}/withdraw', [TaskOfferController::class, 'withdraw'])->name('task-offers.withdraw');
    });

    Route::post('/tasks/{task}/offers', [TaskOfferController::class, 'store'])
        ->middleware(['role:performer', 'throttle:taskora-offers'])
        ->name('tasks.offers.store');

    Route::post('/services/{service:slug}/order', [ServiceOrderController::class, 'store'])
        ->middleware('role:customer')
        ->name('services.order.store');

    Route::post('/tasks/{task}/favorite', [TaskFavoriteController::class, 'store'])->name('tasks.favorite.store');
    Route::delete('/tasks/{task}/favorite', [TaskFavoriteController::class, 'destroy'])->name('tasks.favorite.destroy');
    Route::post('/categories/{category}/favorite', [CategoryFavoriteController::class, 'store'])->name('categories.favorite.store');
    Route::delete('/categories/{category}/favorite', [CategoryFavoriteController::class, 'destroy'])->name('categories.favorite.destroy');
    Route::post('/task-types/favorite/bulk', BulkTaskTypeFavoriteController::class)->name('task-types.favorite.bulk');
    Route::post('/task-types/{taskType}/favorite', [TaskTypeFavoriteController::class, 'store'])->name('task-types.favorite.store');
    Route::delete('/task-types/{taskType}/favorite', [TaskTypeFavoriteController::class, 'destroy'])->name('task-types.favorite.destroy');

    Route::get('/moderator/dashboard', [RoleDashboardController::class, 'moderator'])
        ->middleware('role:moderator')
        ->name('moderator.dashboard');

    Route::middleware('role:moderator,admin')->prefix('moderator')->name('moderator.')->group(function (): void {
        Route::get('/services', [ModeratorServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [ModeratorServiceController::class, 'show'])->name('services.show');
        Route::post('/services/{service}/approve', [ModeratorServiceController::class, 'approve'])->name('services.approve');
        Route::post('/services/{service}/reject', [ModeratorServiceController::class, 'reject'])->name('services.reject');
        Route::get('/performer-profiles', [ModeratorPerformerProfileController::class, 'index'])->name('performer-profiles.index');
        Route::get('/performer-profiles/{profile}', [ModeratorPerformerProfileController::class, 'show'])->name('performer-profiles.show');
        Route::post('/performer-profiles/{profile}/approve', [ModeratorPerformerProfileController::class, 'approve'])->name('performer-profiles.approve');
        Route::post('/performer-profiles/{profile}/reject', [ModeratorPerformerProfileController::class, 'reject'])->name('performer-profiles.reject');
        Route::get('/moderation-flags', [ModerationFlagController::class, 'index'])->name('moderation-flags.index');
        Route::post('/moderation-flags/{flag}/resolve', [ModerationFlagController::class, 'resolve'])->name('moderation-flags.resolve');
        Route::get('/disputes', [ModeratorDisputeController::class, 'index'])->name('disputes.index');
        Route::get('/disputes/{dispute}', [ModeratorDisputeController::class, 'show'])->name('disputes.show');
        Route::post('/disputes/{dispute}/take', [ModeratorDisputeController::class, 'take'])->name('disputes.take');
        Route::post('/disputes/{dispute}/resolve', [ModeratorDisputeController::class, 'resolve'])->name('disputes.resolve');
        Route::post('/disputes/{dispute}/messages', [DisputeMessageController::class, 'store'])
            ->middleware('throttle:taskora-order-messages')
            ->name('disputes.messages.store');
    });

    Route::get('/admin/dashboard', [RoleDashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/finance', AdminFinanceController::class)->name('finance.index');
        Route::get('/mail-settings', AdminMailSettingsController::class)->name('mail-settings.index');
        Route::get('/payment-settings', AdminPaymentSettingsController::class)->name('payment-settings.index');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/events', [AdminOrderController::class, 'events'])->name('orders.events');
        Route::get('/orders/{order}/ledger', [AdminOrderController::class, 'ledger'])->name('orders.ledger');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::post('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
        Route::patch('/users/{user}/admin-note', [AdminUserController::class, 'updateAdminNote'])->name('users.admin-note');
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/toggle-active', [AdminCategoryController::class, 'toggleActive'])->name('categories.toggle-active');
        Route::post('/categories/{category}/move-up', [AdminCategoryController::class, 'moveUp'])->name('categories.move-up');
        Route::post('/categories/{category}/move-down', [AdminCategoryController::class, 'moveDown'])->name('categories.move-down');
        Route::get('/task-types', [AdminTaskTypeController::class, 'index'])->name('task-types.index');
        Route::get('/task-types/create', [AdminTaskTypeController::class, 'create'])->name('task-types.create');
        Route::post('/task-types', [AdminTaskTypeController::class, 'store'])->name('task-types.store');
        Route::get('/task-types/{taskType}/edit', [AdminTaskTypeController::class, 'edit'])->name('task-types.edit');
        Route::patch('/task-types/{taskType}', [AdminTaskTypeController::class, 'update'])->name('task-types.update');
        Route::post('/task-types/{taskType}/toggle-active', [AdminTaskTypeController::class, 'toggleActive'])->name('task-types.toggle-active');
        Route::post('/task-types/{taskType}/move-up', [AdminTaskTypeController::class, 'moveUp'])->name('task-types.move-up');
        Route::post('/task-types/{taskType}/move-down', [AdminTaskTypeController::class, 'moveDown'])->name('task-types.move-down');
        Route::get('/beta-feedback', [AdminBetaFeedbackController::class, 'index'])->name('beta-feedback.index');
        Route::get('/beta-feedback/{feedback}', [AdminBetaFeedbackController::class, 'show'])->name('beta-feedback.show');
        Route::post('/beta-feedback/{feedback}/status', [AdminBetaFeedbackController::class, 'updateStatus'])->name('beta-feedback.status');
    });
});
