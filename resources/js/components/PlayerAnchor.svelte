<script>
    let { player } = $props();
</script>

{#if player}
    <div
        class="fixed bottom-0 left-0 right-0 z-50 flex justify-center pointer-events-none"
    >
        <div
            class="w-full max-w-2xl mx-4 mb-4 px-6 py-3 rounded-xl bg-[color-mix(in_srgb,var(--bg-color)_85%,transparent)] backdrop-blur-xl border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] shadow-[0_0_30px_color-mix(in_srgb,var(--primary-color)_12%,transparent),0_-4px_20px_rgba(0,0,0,0.3)] flex items-center gap-4"
        >
            <div class="relative shrink-0 w-9 h-9">
                {#if player.equipped?.avatar?.image_url}
                    <img
                        src={player.equipped.avatar.image_url}
                        alt={player.name}
                        class="absolute inset-0 w-full h-full rounded-full object-cover"
                    />
                {:else}
                    <div
                        class="absolute inset-0 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] flex items-center justify-center text-xs font-black text-[var(--primary-color)]"
                    >
                        {player.name.slice(0, 2).toUpperCase()}
                    </div>
                {/if}
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-[var(--text-color)] truncate">{player.name}</p>
                <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono">Lv. {player.level}</p>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <div class="text-right">
                    <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] uppercase tracking-widest font-mono">Rank</p>
                    <p class="font-black text-[var(--primary-color)] text-lg leading-none">
                        {player.rank !== null ? `#${player.rank}` : '—'}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] uppercase tracking-widest font-mono">XP</p>
                    <p class="font-black text-[var(--text-color)] text-lg leading-none">{player.xp.toLocaleString()}</p>
                </div>
            </div>
        </div>
    </div>
{/if}
