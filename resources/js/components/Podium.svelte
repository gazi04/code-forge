<script>
    let { leaders = [] } = $props();

    const medals = [
        { label: 'Gold', border: 'border-yellow-400', glow: 'shadow-[0_0_30px_rgba(250,204,21,0.3)]', text: 'text-yellow-400', bg: 'bg-yellow-400/10', size: 'w-20 h-20 text-3xl', order: 'order-2' },
        { label: 'Silver', border: 'border-zinc-400', glow: 'shadow-[0_0_20px_rgba(161,161,170,0.2)]', text: 'text-zinc-400', bg: 'bg-zinc-400/10', size: 'w-16 h-16 text-2xl', order: 'order-1' },
        { label: 'Bronze', border: 'border-amber-600', glow: 'shadow-[0_0_20px_rgba(180,83,9,0.2)]', text: 'text-amber-600', bg: 'bg-amber-600/10', size: 'w-16 h-16 text-2xl', order: 'order-3' },
    ];
</script>

<div class="flex justify-center items-end gap-4 mb-10">
    {#each leaders.slice(0, 3) as leader, i}
        {@const medal = medals[i]}
        <div class="flex flex-col items-center gap-3 {medal.order}">
            <div
                class="{medal.size} rounded-full {medal.bg} border-2 {medal.border} {medal.glow} flex items-center justify-center font-black {medal.text} uppercase tracking-wider"
            >
                {leader.name.slice(0, 2).toUpperCase()}
            </div>

            <div class="text-center">
                <p class="font-bold text-[var(--text-color)] text-sm truncate max-w-[100px]">{leader.name}</p>
                <p class="text-xs {medal.text} font-mono font-bold">Lv. {leader.level}</p>
                <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] font-mono">{leader.xp.toLocaleString()} XP</p>
            </div>

            <div
                class="px-3 py-0.5 rounded-full {medal.bg} border {medal.border} {medal.text} text-xs font-black uppercase tracking-widest"
            >
                #{leader.rank}
            </div>
        </div>
    {/each}
</div>
