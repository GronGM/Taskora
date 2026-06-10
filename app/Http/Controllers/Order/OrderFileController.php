<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderFileRequest;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderFileController extends Controller
{
    public function store(
        StoreOrderFileRequest $request,
        Order $order,
        ContactGuard $contactGuard,
        OrderEventLogger $events,
    ): RedirectResponse {
        $uploadedFile = $request->file('file');
        abort_unless($uploadedFile instanceof UploadedFile, 422);

        $violation = $this->firstContactViolation($uploadedFile, $contactGuard);

        if ($violation) {
            [$reason, $result] = $violation;

            DB::transaction(function () use ($request, $order, $events, $reason, $result, $uploadedFile): void {
                $this->recordModerationFlag($request->user()->id, OrderFile::class, null, $reason, $result);

                $events->contactBlocked($order, $request->user(), [
                    'context' => 'order_file',
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'matched_type' => $result->matchedType,
                ]);
            });

            return back()
                ->withErrors(['file' => 'Файл не загружен: в названии или содержимом обнаружены контактные данные. Обменивайтесь материалами только внутри Таскоры.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $order, $events, $uploadedFile): void {
            $path = $uploadedFile->store("orders/{$order->id}", 'local');
            abort_if($path === false, 500, 'Не удалось сохранить файл.');

            $file = $order->orderFiles()->create([
                'user_id' => $request->user()->id,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => basename($path),
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
                'status' => OrderFile::STATUS_AVAILABLE,
                'moderation_status' => OrderFile::MODERATION_CLEAN,
            ]);

            $events->fileUploaded($order, $request->user(), [
                'file_id' => $file->id,
                'file_name' => $file->original_name,
                'size' => $file->size,
            ]);
        });

        return back()->with('success', 'Файл загружен.');
    }

    public function download(Order $order, OrderFile $file): StreamedResponse
    {
        Gate::authorize('downloadFile', $order);
        abort_unless($file->order_id === $order->id, 404);
        abort_unless($file->status === OrderFile::STATUS_AVAILABLE, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * @return array{0: string, 1: ContactGuardResult}|null
     */
    private function firstContactViolation(UploadedFile $file, ContactGuard $contactGuard): ?array
    {
        $nameResult = $contactGuard->check($file->getClientOriginalName());

        if ($nameResult->failedCheck()) {
            return ['contact_detected_in_order_file_name', $nameResult];
        }

        $content = $this->textContentForGuard($file);

        if ($content === null) {
            return null;
        }

        $contentResult = $contactGuard->check($content);

        if ($contentResult->failedCheck()) {
            return ['contact_detected_in_order_file_content', $contentResult];
        }

        return null;
    }

    private function textContentForGuard(UploadedFile $file): ?string
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['txt', 'csv'], true)) {
            return null;
        }

        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            return null;
        }

        return file_get_contents($path) ?: '';
    }

    private function recordModerationFlag(
        int $userId,
        string $entityType,
        ?int $entityId,
        string $reason,
        ContactGuardResult $result,
    ): void {
        ModerationFlag::create([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reason' => $reason,
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
