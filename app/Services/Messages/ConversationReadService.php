<?php

namespace App\Services\Messages;

use App\Models\ConversationRead;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ConversationReadService
{
    public function unreadCount(User $user): int
    {
        return $this->unreadOrderMessagesCount($user) + $this->unreadDisputeMessagesCount($user);
    }

    public function unreadOrderCount(User $user, Order $order): int
    {
        return $this->orderUnreadQuery($user)
            ->where('orders.id', $order->id)
            ->count();
    }

    public function unreadDisputeCount(User $user, Dispute $dispute): int
    {
        return $this->disputeUnreadQuery($user)
            ->where('disputes.id', $dispute->id)
            ->count();
    }

    public function unreadOrderMessagesCount(User $user): int
    {
        return $this->orderUnreadQuery($user)->count();
    }

    public function unreadDisputeMessagesCount(User $user): int
    {
        return $this->disputeUnreadQuery($user)->count();
    }

    public function markOrderRead(User $user, Order $order): ConversationRead
    {
        return $this->markRead($user, ConversationRead::TYPE_ORDER, $order->id);
    }

    public function markDisputeRead(User $user, Dispute $dispute): ConversationRead
    {
        return $this->markRead($user, ConversationRead::TYPE_DISPUTE, $dispute->id);
    }

    private function markRead(User $user, string $type, int $id): ConversationRead
    {
        return ConversationRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'conversation_type' => $type,
                'conversation_id' => $id,
            ],
            ['last_read_at' => now()],
        );
    }

    private function orderUnreadQuery(User $user): Builder
    {
        return DB::table('order_messages')
            ->join('orders', 'orders.id', '=', 'order_messages.order_id')
            ->leftJoin('conversation_reads', function (JoinClause $join) use ($user): void {
                $join
                    ->on('conversation_reads.conversation_id', '=', 'orders.id')
                    ->where('conversation_reads.user_id', '=', $user->id)
                    ->where('conversation_reads.conversation_type', '=', ConversationRead::TYPE_ORDER);
            })
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('orders.customer_id', $user->id)
                    ->orWhere('orders.performer_id', $user->id);
            })
            ->where('order_messages.user_id', '!=', $user->id)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('conversation_reads.last_read_at')
                    ->orWhereColumn('order_messages.created_at', '>', 'conversation_reads.last_read_at');
            });
    }

    private function disputeUnreadQuery(User $user): Builder
    {
        return DB::table('dispute_messages')
            ->join('disputes', 'disputes.id', '=', 'dispute_messages.dispute_id')
            ->join('orders', 'orders.id', '=', 'disputes.order_id')
            ->leftJoin('conversation_reads', function (JoinClause $join) use ($user): void {
                $join
                    ->on('conversation_reads.conversation_id', '=', 'disputes.id')
                    ->where('conversation_reads.user_id', '=', $user->id)
                    ->where('conversation_reads.conversation_type', '=', ConversationRead::TYPE_DISPUTE);
            })
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('orders.customer_id', $user->id)
                    ->orWhere('orders.performer_id', $user->id);

                if ($user->isModerator() || $user->isAdmin()) {
                    $query->orWhereIn('disputes.status', [
                        Dispute::STATUS_OPEN,
                        Dispute::STATUS_UNDER_REVIEW,
                        Dispute::STATUS_RESOLVED,
                        Dispute::STATUS_CANCELED,
                    ]);
                }
            })
            ->where('dispute_messages.user_id', '!=', $user->id)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('conversation_reads.last_read_at')
                    ->orWhereColumn('dispute_messages.created_at', '>', 'conversation_reads.last_read_at');
            });
    }
}
