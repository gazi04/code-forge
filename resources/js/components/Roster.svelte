<script>
    let { leaders = [], playerName = '' } = $props();
</script>

<div class="space-y-2">
    {#each leaders.slice(3) as leader}
        {@const isPlayer = leader.name === playerName}
        <div
            class="flex items-center gap-4 px-5 py-3 rounded-xl transition-colors duration-200
                {isPlayer
                    ? 'bg-[color-mix(in_srgb,var(--primary-color)_12%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_10%,transparent)]'
                    : 'bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_15%,transparent)]'}"
        >
            <span class="w-8 text-center font-mono text-sm text-[color-mix(in_srgb,var(--text-color)_40%,transparent)]">
                #{leader.rank}
            </span>

            <div
                class="w-8 h-8 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_15%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_25%,transparent)] flex items-center justify-center text-xs font-black text-[var(--primary-color)]"
            >
                {leader.name.slice(0, 2).toUpperCase()}
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm text-[var(--text-color)] truncate {isPlayer ? 'text-[var(--primary-color)]' : ''}">
                    {leader.name}{isPlayer ? ' (You)' : ''}
                </p>
                <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono">Lv. {leader.level}</p>
            </div>

            <span class="font-mono text-sm font-bold text-[color-mix(in_srgb,var(--text-color)_70%,transparent)]">
                {leader.xp.toLocaleString()} XP
            </span>
        </div>
    {/each}

    {#if leaders.slice(3).length === 0}
        <div class="text-center py-12 text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] font-mono text-sm">
            No other cadets ranked yet.
        </div>
    {/if}
</div>
