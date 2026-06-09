<script>
    import { page, router } from '@inertiajs/svelte';
    import Layout from '../../../layouts/StudentLayout.svelte';
    import ItemCard from '../../../components/Store/ItemCard.svelte';

    let { items = [], inventory = [], equipped = {}, tab = 'shop' } = $props();

    const CATEGORIES = [
        { key: 'all', label: 'All' },
        { key: 'title', label: 'Titles' },
        { key: 'avatar', label: 'Avatars' },
        { key: 'streak_freeze', label: 'Streak Freezes' },
        { key: 'xp_boost', label: 'XP Boosts' },
    ];

    const INVENTORY_GROUPS = [
        { key: 'title', label: 'Titles' },
        { key: 'avatar', label: 'Avatars' },
        { key: 'streak_freeze', label: 'Streak Freezes' },
        { key: 'xp_boost', label: 'XP Boosts' },
    ];

    let selectedCategory = $state('all');

    let coins = $derived(page.props.auth?.user?.coins ?? 0);

    let inventoryMap = $derived(
        Object.fromEntries(inventory.map((inv) => [inv.store_item_id, inv])),
    );

    let filteredItems = $derived(
        selectedCategory === 'all' ? items : items.filter((item) => item.type === selectedCategory),
    );

    let inventoryByType = $derived(
        INVENTORY_GROUPS.reduce((acc, group) => {
            acc[group.key] = inventory.filter((inv) => inv.store_item.type === group.key);
            return acc;
        }, {}),
    );

    let storeResult = $derived(page.props.flash?.store_result);

    function switchTab(newTab) {
        router.visit(`/store?tab=${newTab}`, {
            preserveState: false,
            preserveScroll: true,
        });
    }

    function handlePurchase(itemId) {
        router.post(`/store/${itemId}/purchase`, {}, { preserveScroll: true });
    }

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

<Layout>
    <div class="max-w-4xl mx-auto pb-16 px-4">
        <!-- Header -->
        <div class="mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-black tracking-wide text-[var(--text-color)]">
                    🛍️ Store
                </h1>
                <p class="text-sm text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono uppercase tracking-widest mt-1">
                    Spend your coins
                </p>
            </div>

            <div
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-yellow-500/10 border border-yellow-500/25 text-yellow-400 font-mono font-bold text-sm"
            >
                <span>💰</span>
                {coins.toLocaleString()} coins
            </div>
        </div>

        <!-- Error / Success flash -->
        {#if storeResult?.error}
            <div
                class="mb-6 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm font-mono"
            >
                {storeResult.error}
            </div>
        {/if}
        {#if storeResult?.purchased}
            <div
                class="mb-6 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-mono"
            >
                ✓ {storeResult.purchased} added to your inventory.
            </div>
        {/if}
        {#if storeResult?.activated}
            <div
                class="mb-6 px-4 py-3 rounded-xl bg-[color-mix(in_srgb,var(--accent-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--accent-color)_30%,transparent)] text-[var(--accent-color)] text-sm font-mono"
            >
                ⚡ {storeResult.activated} activated.
            </div>
        {/if}

        <!-- Tab switcher -->
        <div class="flex gap-2 mb-8">
            <button
                onclick={() => switchTab('shop')}
                class="px-5 py-2 rounded-xl text-sm font-black uppercase tracking-widest transition-all
                    {tab === 'shop'
                        ? 'bg-[var(--primary-color)] text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]'
                        : 'bg-[var(--surface-color)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'}"
            >
                Shop
            </button>
            <button
                onclick={() => switchTab('inventory')}
                class="px-5 py-2 rounded-xl text-sm font-black uppercase tracking-widest transition-all
                    {tab === 'inventory'
                        ? 'bg-[var(--primary-color)] text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]'
                        : 'bg-[var(--surface-color)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'}"
            >
                My Inventory
                {#if inventory.length > 0}
                    <span
                        class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] bg-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] text-[var(--primary-color)]"
                    >
                        {inventory.length}
                    </span>
                {/if}
            </button>
        </div>

        <!-- SHOP TAB -->
        {#if tab === 'shop'}
            <!-- Category filter -->
            <div class="flex flex-wrap gap-2 mb-6">
                {#each CATEGORIES as cat}
                    <button
                        onclick={() => (selectedCategory = cat.key)}
                        class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-colors
                            {selectedCategory === cat.key
                                ? 'bg-[color-mix(in_srgb,var(--primary-color)_15%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] text-[var(--primary-color)]'
                                : 'bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] hover:text-[var(--text-color)]'}"
                    >
                        {cat.label}
                    </button>
                {/each}
            </div>

            {#if filteredItems.length === 0}
                <div
                    class="text-center py-20 text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] font-mono"
                >
                    <p class="text-4xl mb-4">📦</p>
                    <p class="text-sm uppercase tracking-widest">No items in this category yet.</p>
                </div>
            {:else}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    {#each filteredItems as item (item.id)}
                        {@const inv = inventoryMap[item.id]}
                        <ItemCard
                            {item}
                            mode="shop"
                            owned={!!inv}
                            equipped={equipped[item.type] === item.id}
                            affordable={coins >= item.price_coins}
                            onPurchase={() => handlePurchase(item.id)}
                        />
                    {/each}
                </div>
            {/if}

        <!-- INVENTORY TAB -->
        {:else}
            {#if inventory.length === 0}
                <div
                    class="text-center py-20 text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] font-mono"
                >
                    <p class="text-4xl mb-4">🎒</p>
                    <p class="text-sm uppercase tracking-widest">
                        Your inventory is empty. Visit the shop to spend your coins.
                    </p>
                </div>
            {:else}
                <div class="space-y-8">
                    {#each INVENTORY_GROUPS as group}
                        {@const groupItems = inventoryByType[group.key] ?? []}
                        {#if groupItems.length > 0}
                            <section>
                                <h2
                                    class="text-xs font-black uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] mb-3"
                                >
                                    {group.label}
                                </h2>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
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
        {/if}
    </div>
</Layout>
