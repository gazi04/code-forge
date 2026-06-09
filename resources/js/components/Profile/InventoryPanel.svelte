<script>
    import { router } from '@inertiajs/svelte';
    import ItemCard from '@/components/Store/ItemCard.svelte';

    let { inventory = [], equipped = {} } = $props();

    const GROUPS = [
        { key: 'title', label: 'Titles' },
        { key: 'avatar', label: 'Avatars' },
        { key: 'streak_freeze', label: 'Streak Freezes' },
        { key: 'xp_boost', label: 'XP Boosts' },
    ];

    let byType = $derived(
        GROUPS.reduce((acc, group) => {
            acc[group.key] = inventory.filter((inv) => inv.store_item.type === group.key);
            return acc;
        }, {}),
    );

    let hasItems = $derived(inventory.length > 0);

    function handleEquip(inventoryId) {
        router.post(`/inventory/${inventoryId}/equip`, {}, { preserveScroll: true });
    }

    function handleUnequip(type) {
        router.delete(`/inventory/unequip/${type}`, { preserveScroll: true });
    }

    function handleActivate(inventoryId) {
        router.post(`/inventory/${inventoryId}/activate`, {}, { preserveScroll: true });
    }
</script>

<div class="bg-surface rounded-2xl p-6 mb-6">
    <h2 class="text-sm font-black uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] mb-5">
        Inventory
    </h2>

    {#if !hasItems}
        <div class="text-center py-10 text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] font-mono text-sm">
            <p class="text-3xl mb-3">🎒</p>
            <p class="uppercase tracking-widest text-xs">No items yet. Visit the store.</p>
        </div>
    {:else}
        <div class="space-y-6">
            {#each GROUPS as group}
                {@const groupItems = byType[group.key] ?? []}
                {#if groupItems.length > 0}
                    <section>
                        <h3 class="text-xs font-black uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] mb-3">
                            {group.label}
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            {#each groupItems as inv (inv.id)}
                                <ItemCard
                                    item={inv.store_item}
                                    mode="inventory"
                                    owned={true}
                                    equipped={equipped[inv.store_item.type] === inv.store_item_id}
                                    affordable={true}
                                    quantity={inv.quantity}
                                    inventoryId={inv.id}
                                    onEquip={() => handleEquip(inv.id)}
                                    onUnequip={() => handleUnequip(inv.store_item.type)}
                                    onActivate={() => handleActivate(inv.id)}
                                />
                            {/each}
                        </div>
                    </section>
                {/if}
            {/each}
        </div>
    {/if}
</div>
