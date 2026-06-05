<script>
    let { ledger } = $props();

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function entryIcon(type) {
        return type === 'lesson' ? '📜' : '⚡';
    }
</script>

<div class="bg-surface rounded-2xl p-6 mb-6">
    <h2 class="text-sm font-mono uppercase tracking-widest text-white/40 mb-4">Quest Ledger</h2>

    {#if ledger.length === 0}
        <p class="text-center text-white/30 font-mono text-sm py-8">
            No completions yet. Start a lesson to build your ledger.
        </p>
    {:else}
        <ol class="relative border-l border-white/10 ml-3 space-y-0">
            {#each ledger as entry, i (i)}
                <li class="mb-6 ml-6">
                    <span
                        class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_15%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] text-xs transition-colors duration-500"
                    >
                        {entryIcon(entry.type)}
                    </span>

                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-white/90 leading-tight capitalize">
                                {entry.label.replace(/-/g, ' ')}
                            </p>
                            <time class="text-[10px] font-mono text-white/30 uppercase tracking-wider">
                                {formatDate(entry.completed_at)}
                            </time>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span
                                class="text-[10px] font-mono text-[var(--primary-color)] bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] px-2 py-0.5 rounded-full border border-[color-mix(in_srgb,var(--primary-color)_25%,transparent)] transition-colors duration-500"
                            >
                                +{entry.xp} XP
                            </span>
                            <span
                                class="text-[10px] font-mono text-yellow-400 bg-yellow-400/10 px-2 py-0.5 rounded-full border border-yellow-400/20"
                            >
                                +{entry.coins} 💰
                            </span>
                        </div>
                    </div>
                </li>
            {/each}
        </ol>
    {/if}
</div>
