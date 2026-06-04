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
    class="bg-[#150b2e] px-6 py-4 border-b border-indigo-900/50 flex justify-between items-center"
>
    <div class="flex items-center gap-4">
        <div
            class="w-10 h-10 rounded-full bg-indigo-900/40 border border-indigo-500/30 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(99,102,241,0.2)]"
        >
            {icon}
        </div>
        <div>
            <h4
                class="font-serif font-bold text-indigo-100 text-lg tracking-wide"
            >
                {title}
            </h4>

            <p class="text-xs text-indigo-300/60 font-mono mt-0.5">
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
