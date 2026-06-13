<script>
    let {
        item,
        mode = 'shop',
        owned = false,
        equipped = false,
        affordable = true,
        quantity = 1,
        inventoryId: _inventoryId = null,
        onPurchase,
        onEquip,
        onUnequip,
        onActivate,
    } = $props();

    let confirmingActivate = $state(false);

    const TYPE_COLORS = {
        title: 'text-sky-400',
        avatar: 'text-emerald-400',
        streak_freeze: 'text-orange-400',
        xp_boost: 'text-violet-400',
    };

    let isCosmetic = $derived(item.type === 'title' || item.type === 'avatar');
    let isLimited = $derived(item.purchase_type === 'one_time');
    let stockLeft = $derived(isLimited ? item.stock_limit - item.sold_count : null);
    let isSoldOut = $derived(isLimited && stockLeft !== null && stockLeft <= 0);
</script>

<div
    class="bg-surface rounded-2xl overflow-hidden border transition-all duration-200
        {equipped
            ? 'border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] shadow-[0_0_16px_color-mix(in_srgb,var(--primary-color)_10%,transparent)]'
            : 'border-[color-mix(in_srgb,var(--text-color)_6%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_15%,transparent)]'}"
>
    <!-- Image -->
    <div class="aspect-square w-full bg-[color-mix(in_srgb,var(--text-color)_4%,transparent)] flex items-center justify-center overflow-hidden">
        {#if item.image_url}
            <img src={item.image_url} alt={item.name} class="w-full h-full object-cover" />
        {:else}
            <span class="text-4xl">{item.icon ?? '📦'}</span>
        {/if}
    </div>

    <!-- Info + Action -->
    <div class="p-3 flex flex-col gap-2">
        <div>
            <p class="font-bold text-sm text-[var(--text-color)] leading-tight">{item.name}</p>
            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                <span class="text-[10px] font-black uppercase tracking-widest {TYPE_COLORS[item.type] ?? 'text-zinc-400'}">
                    {item.type.replace('_', ' ')}
                </span>
                {#if equipped}
                    <span class="text-[10px] font-black uppercase tracking-widest text-[var(--primary-color)]">· Equipped</span>
                {/if}
                {#if mode === 'inventory' && item.purchase_type === 'consumable'}
                    <span class="text-[10px] font-mono text-[color-mix(in_srgb,var(--text-color)_40%,transparent)]">×{quantity}</span>
                {/if}
                {#if mode === 'shop'}
                    <span class="text-[10px] font-mono font-bold text-yellow-400 ml-auto">💰 {item.price_coins.toLocaleString()}</span>
                {/if}
            </div>
        </div>

        <!-- Action -->
        {#if mode === 'shop'}
            {#if isSoldOut || (!affordable && !owned)}
                <button disabled class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-[color-mix(in_srgb,var(--text-color)_25%,transparent)] cursor-not-allowed">
                    {isSoldOut ? 'Sold Out' : "Can't Afford"}
                </button>
            {:else if equipped || (owned && !isCosmetic)}
                <button disabled class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] text-[var(--primary-color)] cursor-not-allowed opacity-60">
                    {equipped ? 'Equipped' : 'Owned'}
                </button>
            {:else}
                <button onclick={onPurchase} class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[var(--primary-color)] text-[var(--bg-color)] hover:opacity-90 transition-opacity active:scale-95">
                    Buy
                </button>
            {/if}

        {:else if mode === 'inventory'}
            {#if isCosmetic}
                {#if equipped}
                    <button onclick={onUnequip} class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[color-mix(in_srgb,var(--text-color)_6%,transparent)] text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] hover:bg-[color-mix(in_srgb,var(--text-color)_10%,transparent)] transition-colors">
                        Unequip
                    </button>
                {:else}
                    <button onclick={onEquip} class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[var(--primary-color)] text-[var(--bg-color)] hover:opacity-90 transition-opacity active:scale-95">
                        Equip
                    </button>
                {/if}
            {:else if confirmingActivate}
                <div class="flex gap-1">
                    <button
                        onclick={() => {
 confirmingActivate = false; onActivate?.(); 
}}
                        class="flex-1 py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 transition-colors"
                    >
                        Confirm
                    </button>
                    <button
                        onclick={() => (confirmingActivate = false)}
                        class="flex-1 py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[color-mix(in_srgb,var(--text-color)_6%,transparent)] text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] hover:bg-[color-mix(in_srgb,var(--text-color)_10%,transparent)] transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            {:else}
                <button onclick={() => (confirmingActivate = true)} class="w-full py-2.5 sm:py-1.5 rounded-lg text-xs font-black uppercase tracking-wider bg-[color-mix(in_srgb,var(--accent-color)_12%,transparent)] text-[var(--accent-color)] hover:bg-[color-mix(in_srgb,var(--accent-color)_20%,transparent)] transition-colors">
                    Use
                </button>
            {/if}
        {/if}
    </div>
</div>
