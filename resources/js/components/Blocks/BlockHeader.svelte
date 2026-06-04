<script>
    let {
        icon = '🎮',
        title = 'System Challenge',
        instructions = 'Follow the directives to proceed.',
        isRequired = false,
        isCorrect = false,
        xpReward = 0,
        coinReward = 0,
    } = $props();
</script>

<div
    class="bg-[var(--surface-color)] px-6 py-4 border-b border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] flex justify-between items-center"
>
    <div class="flex items-center gap-4">
        <div
            class="w-10 h-10 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] flex items-center justify-center text-xl shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_20%,transparent)]"
        >
            {icon}
        </div>
        <div>
            <h4
                class="font-serif font-bold text-[var(--text-color)] text-lg tracking-wide"
            >
                {title}
            </h4>

            <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono mt-0.5">
                {instructions}
            </p>

            {#if isRequired && !isCorrect}
                <span
                    class="px-2 py-0.5 rounded text-[10px] uppercase font-black bg-red-500/20 text-red-400 border border-red-500/30 inline-block mt-1"
                >
                    Required
                </span>
            {/if}
        </div>
    </div>

    {#if (xpReward > 0 || coinReward > 0) && !isCorrect}
        <div class="flex gap-2 text-s font-mono opacity-70">
            {#if xpReward > 0}<span>✨ +{xpReward}</span>{/if}
            {#if coinReward > 0}<span>💰 +{coinReward}</span>{/if}
        </div>
    {:else if isCorrect && (xpReward > 0 || coinReward > 0)}
        <div
            class="text-xs font-mono text-[var(--primary-color)] animate-bounce"
        >
            Loot Acquired!
        </div>
    {/if}
</div>
