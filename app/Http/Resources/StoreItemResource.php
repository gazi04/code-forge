<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\PurchaseType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StoreItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Only the fields the store/inventory UI renders. Economy internals
     * (`effect_config`, `display_config`, `is_active`, raw `stock_limit`/`sold_count`)
     * stay server-side; the client gets the derived `stock_remaining` instead.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'purchase_type' => $this->purchase_type->value,
            'price_coins' => $this->price_coins,
            'icon' => $this->icon,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'stock_remaining' => $this->purchase_type === PurchaseType::OneTime && $this->stock_limit !== null
                ? max(0, $this->stock_limit - $this->sold_count)
                : null,
        ];
    }
}
