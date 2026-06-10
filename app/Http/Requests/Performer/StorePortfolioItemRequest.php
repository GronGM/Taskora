<?php

namespace App\Http\Requests\Performer;

use App\Models\PerformerPortfolioItem;
use Illuminate\Support\Facades\Gate;

class StorePortfolioItemRequest extends PortfolioItemPayloadRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PerformerPortfolioItem::class);
    }
}
