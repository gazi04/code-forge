<script>
    let { ledger } = $props();

    const INITIAL_COUNT = 5;
    let showAll = $state(false);
    let visibleEntries = $derived(showAll ? ledger : ledger.slice(0, INITIAL_COUNT));

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    const TYPE_CONFIG = {
        lesson: { icon: '📜', label: 'Lesson', color: 'var(--primary-color)' },
        block: { icon: '⚡', label: 'Block', color: 'var(--accent-color)' },
    };
</script>

<div class="bg-surface rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-black text-[var(--text-color)] tracking-tight">
            Quest Ledger
        </h2>
        {#if ledger.length > 10}
            <span
                class="text-xs font-mono text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] uppercase tracking-widest"
            >
                Last 10 of {ledger.length}
            </span>
        {/if}
    </div>

    {#if ledger.length === 0}
        <div class="text-center py-14">
            <p class="text-4xl mb-4">📜</p>
            <p
                class="text-sm font-black uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]"
            >
                No missions logged yet
            </p>
            <p
                class="text-sm text-[color-mix(in_srgb,var(--text-color)_20%,transparent)] mt-1"
            >
                Complete a lesson to fill your ledger.
            </p>
        </div>
    {:else}
        <div class="space-y-2.5">
            {#each visibleEntries as entry, i (i)}
                {@const config = TYPE_CONFIG[entry.type] ?? TYPE_CONFIG.block}
                <div class="entry-row group flex items-center gap-4 px-4 py-3.5 rounded-xl bg-[color-mix(in_srgb,var(--text-color)_3%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_7%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] transition-all duration-200">
                    <!-- Icon -->
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-500"
                    >
                        {config.icon}
                    </div>

                    <!-- Label + time -->
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-sm font-bold text-[var(--text-color)] capitalize leading-tight truncate"
                        >
                            {entry.label.replace(/-/g, ' ')}
                        </p>
                        <time
                            class="text-xs font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)]"
                        >
                            {formatDate(entry.completed_at)}
                        </time>
                    </div>

                    <!-- Rewards -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div
                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-500"
                        >
                            <span class="text-xs">✨</span>
                            <span
                                class="text-xs font-black font-mono text-[var(--primary-color)]"
                                >+{entry.xp}</span
                            >
                        </div>
                        <div
                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-yellow-400/10 border border-yellow-400/20"
                        >
                            <span class="text-xs">💰</span>
                            <span
                                class="text-xs font-black font-mono text-yellow-400"
                                >+{entry.coins}</span
                            >
                        </div>
                    </div>
                </div>
            {/each}
        </div>

        {#if ledger.length > INITIAL_COUNT}
            <button
                onclick={() => (showAll = !showAll)}
                class="mt-4 w-full text-xs font-bold uppercase tracking-widest py-2 rounded-xl border transition-all duration-200 text-[var(--primary-color)] border-[color-mix(in_srgb,var(--primary-color)_25%,transparent)] bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] hover:bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)]"
            >
                {showAll ? 'Show less' : `Show ${ledger.length - INITIAL_COUNT} more`}
            </button>
        {/if}
    {/if}
</div>

<style>
    .entry-row {
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            border-color 0.2s ease;
    }
    .entry-row:hover {
        transform: translateX(2px);
        box-shadow: 0 0 16px color-mix(in srgb, var(--primary-color) 8%, transparent);
    }
</style>
