<script>
    let { achievements = [] } = $props();

    let unlocked = $derived(achievements.filter((a) => a.unlocked));
    let locked = $derived(achievements.filter((a) => !a.unlocked));

    function formatDate(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }
</script>

<div class="bg-surface rounded-2xl p-6 mb-6">
    <h2 class="text-lg font-black text-white mb-1 tracking-tight">
        Achievements
    </h2>
    <p class="text-xs font-mono text-white/40 uppercase tracking-widest mb-5">
        {unlocked.length} / {achievements.length} Unlocked
    </p>

    {#if achievements.length === 0}
        <p class="text-sm text-white/30 font-mono text-center py-8">
            No achievements configured yet.
        </p>
    {:else}
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
            {#each unlocked as achievement (achievement.id)}
                <div
                    class="achievement-card unlocked group relative flex flex-col items-center gap-2 p-3 rounded-xl bg-black/30 border border-white/10 cursor-default"
                >
                    <div class="achievement-icon">
                        {#if achievement.image_path}
                            <img
                                src={`/storage/${achievement.image_path}`}
                                alt={achievement.name}
                                class="w-12 h-12 object-contain drop-shadow-[0_0_8px_var(--primary-color)]"
                            />
                        {:else}
                            <div
                                class="w-12 h-12 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-2xl shadow-[0_0_12px_var(--primary-color)]"
                            >
                                🏆
                            </div>
                        {/if}
                    </div>

                    <span
                        class="text-[11px] font-bold text-white text-center leading-tight line-clamp-2"
                    >
                        {achievement.name}
                    </span>

                    {#if achievement.unlocked_at}
                        <span
                            class="text-[9px] font-mono text-[var(--accent-color)] text-center"
                        >
                            {formatDate(achievement.unlocked_at)}
                        </span>
                    {/if}

                    <!-- Tooltip -->
                    <div
                        class="achievement-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-44 bg-black/90 border border-white/10 rounded-xl p-3 text-xs text-white/80 pointer-events-none z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                    >
                        <p class="font-bold text-white mb-1">
                            {achievement.name}
                        </p>
                        {#if achievement.description}
                            <p class="text-white/60 leading-snug">
                                {achievement.description}
                            </p>
                        {/if}
                    </div>
                </div>
            {/each}

            {#each locked as achievement (achievement.id)}
                <div
                    class="achievement-card locked group relative flex flex-col items-center gap-2 p-3 rounded-xl bg-black/20 border border-white/5 cursor-default"
                >
                    <div class="achievement-icon opacity-30 grayscale">
                        {#if achievement.image_path}
                            <img
                                src={`/storage/${achievement.image_path}`}
                                alt={achievement.name}
                                class="w-12 h-12 object-contain"
                            />
                        {:else}
                            <div
                                class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-2xl"
                            >
                                🏆
                            </div>
                        {/if}
                    </div>

                    <span
                        class="text-[11px] font-bold text-white/25 text-center leading-tight line-clamp-2"
                    >
                        {achievement.name}
                    </span>

                    <!-- Tooltip with description -->
                    <div
                        class="achievement-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-44 bg-black/90 border border-white/10 rounded-xl p-3 text-xs text-white/80 pointer-events-none z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                    >
                        <p class="font-bold text-white mb-1">
                            {achievement.name}
                        </p>
                        {#if achievement.description}
                            <p class="text-white/60 leading-snug mb-2">
                                {achievement.description}
                            </p>
                        {/if}
                        <p class="text-white/30 italic text-[10px]">
                            🔒 Not yet unlocked
                        </p>
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</div>

<style>
    .achievement-card {
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }
    .achievement-card.unlocked:hover {
        transform: translateY(-2px);
        box-shadow:
            0 0 16px rgba(var(--primary-color), 0.3),
            var(--card-shadow);
    }
    .achievement-tooltip {
        transition: opacity 0.2s ease;
    }
</style>
