<?php

namespace App\Http\Requests\Performer;

use App\Models\PerformerPortfolioItem;
use Illuminate\Support\Facades\Gate;

class UpdatePortfolioItemRequest extends PortfolioItemPayloadRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof PerformerPortfolioItem && Gate::allows('update', $item);
    }
}
