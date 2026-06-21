<?php

namespace App\Services;

use App\Models\StoreItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EquippedItemResolver
{
    /**
     * The equipped store-item ids (title + avatar) for a user.
     *
     * @return array<int, mixed>
     */
    public function equippedItemIds(User $user): array
    {
        $prefs = $user->preferences ?? [];

        return array_filter([
            $prefs['equipped_title'] ?? null,
            $prefs['equipped_avatar'] ?? null,
        ]);
    }

    /**
     * Fetch the store items for the given equipped ids, keyed by id.
     *
     * @param  array<int, mixed>  $ids
     * @return Collection<int, StoreItem>
     */
    public function fetchEquippedItems(array $ids): Collection
    {
        return $ids
            ? StoreItem::whereIn('id', $ids)->select(['id', 'name', 'type', 'image', 'display_config'])->get()->keyBy('id')
            : collect();
    }

    /**
     * Resolve a single user's equipped title/avatar payload (fetches its own items).
     *
     * @return array{title: array<string, mixed>|null, avatar: array<string, mixed>|null}
     */
    public function resolveEquipped(User $user): array
    {
        return $this->buildEquipped($user, $this->fetchEquippedItems($this->equippedItemIds($user)));
    }

    /**
     * Build the equipped payload from a pre-fetched item collection (batch-friendly).
     *
     * @param  Collection<int, StoreItem>  $equippedItems
     * @return array{title: array<string, mixed>|null, avatar: array<string, mixed>|null}
     */
    public function buildEquipped(User $user, Collection $equippedItems): array
    {
        $prefs = $user->preferences ?? [];
        $titleId = $prefs['equipped_title'] ?? null;
        $avatarId = $prefs['equipped_avatar'] ?? null;

        $title = $titleId && $equippedItems->has($titleId) ? $equippedItems->get($titleId) : null;
        $avatar = $avatarId && $equippedItems->has($avatarId) ? $equippedItems->get($avatarId) : null;

        return [
            'title' => $title ? [
                'id' => $title->id,
                'name' => $title->name,
                'color' => $title->display_config['color'] ?? null,
            ] : null,
            'avatar' => $avatar ? [
                'id' => $avatar->id,
                'name' => $avatar->name,
                'image_url' => $avatar->image ? Storage::disk('public')->url($avatar->image) : null,
            ] : null,
        ];
    }
}
