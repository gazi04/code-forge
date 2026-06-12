<script>
    let { achievements = [] } = $props();

    const UNLOCKED_INITIAL = 10;
    let unlocked = $derived(achievements.filter((a) => a.unlocked));
    let locked = $derived(achievements.filter((a) => !a.unlocked));

    let showAllUnlocked = $state(false);
    let showLocked = $state(false);

    let visibleUnlocked = $derived(showAllUnlocked ? unlocked : unlocked.slice(0, UNLOCKED_INITIAL));

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
    <div class="flex items-baseline justify-between mb-5">
        <h2 class="text-lg font-black text-[var(--text-color)] tracking-tight">
            Achievements
        </h2>
        <span
            class="text-xs font-mono font-bold px-2.5 py-1 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_12%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] text-[var(--primary-color)] transition-colors duration-500"
        >
            {unlocked.length} / {achievements.length}
        </span>
    </div>

    {#if achievements.length === 0}
        <p
            class="text-sm text-[color-mix(in_srgb,var(--text-color)_25%,transparent)] font-mono text-center py-12"
        >
            No achievements configured yet.
        </p>
    {:else}
        {#if unlocked.length > 0}
            <p
                class="text-xs font-mono uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] mb-3"
            >
                Unlocked
            </p>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-3">
                {#each visibleUnlocked as achievement (achievement.id)}
                    <div
                        class="achievement-card unlocked group relative flex flex-col items-center gap-2 p-3 rounded-xl bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] cursor-default transition-colors duration-500"
                    >
                        <div class="achievement-icon">
                            {#if achievement.image_path}
                                <img
                                    src={`/storage/${achievement.image_path}`}
                                    alt={achievement.name}
                                    class="w-12 h-12 object-contain drop-shadow-[0_0_8px_color-mix(in_srgb,var(--primary-color)_70%,transparent)]"
                                />
                            {:else}
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-2xl shadow-[0_0_12px_color-mix(in_srgb,var(--primary-color)_40%,transparent)]"
                                >
                                    🏆
                                </div>
                            {/if}
                        </div>

                        <span
                            class="text-xs font-bold text-[var(--text-color)] text-center leading-tight line-clamp-2"
                        >
                            {achievement.name}
                        </span>

                        {#if achievement.unlocked_at}
                            <span
                                class="text-[11px] font-mono text-[var(--accent-color)] text-center transition-colors duration-500"
                            >
                                {formatDate(achievement.unlocked_at)}
                            </span>
                        {/if}

                        <div
                            class="achievement-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-44 bg-[var(--bg-color)] border border-[color-mix(in_srgb,var(--text-color)_12%,transparent)] rounded-xl p-3 text-xs pointer-events-none z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 shadow-xl"
                        >
                            <p
                                class="font-bold text-[var(--text-color)] mb-1"
                            >
                                {achievement.name}
                            </p>
                            {#if achievement.description}
                                <p
                                    class="text-[color-mix(in_srgb,var(--text-color)_55%,transparent)] leading-snug"
                                >
                                    {achievement.description}
                                </p>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>

            {#if unlocked.length > UNLOCKED_INITIAL}
                <button
                    onclick={() => (showAllUnlocked = !showAllUnlocked)}
                    class="mb-3 w-full text-xs font-bold uppercase tracking-widest py-2 rounded-xl border transition-all duration-200 text-[var(--primary-color)] border-[color-mix(in_srgb,var(--primary-color)_25%,transparent)] bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] hover:bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)]"
                >
                    {showAllUnlocked ? 'Show less' : `Show ${unlocked.length - UNLOCKED_INITIAL} more`}
                </button>
            {/if}
        {/if}

        {#if locked.length > 0}
            <div
                class="border-t border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] pt-4"
            >
                <button
                    onclick={() => (showLocked = !showLocked)}
                    class="w-full text-xs font-bold uppercase tracking-widest py-2 rounded-xl border transition-all duration-200 text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] border-[color-mix(in_srgb,var(--text-color)_12%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_3%,transparent)] hover:bg-[color-mix(in_srgb,var(--text-color)_6%,transparent)]"
                >
                    {showLocked ? `Hide locked` : `Show ${locked.length} locked`}
                </button>

            {#if showLocked}
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mt-4">
                    {#each locked as achievement (achievement.id)}
                        <div
                            class="group relative flex flex-col items-center gap-2 p-3 rounded-xl bg-[color-mix(in_srgb,var(--text-color)_3%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_6%,transparent)] cursor-default"
                        >
                            <div class="opacity-25 grayscale">
                                {#if achievement.image_path}
                                    <img
                                        src={`/storage/${achievement.image_path}`}
                                        alt={achievement.name}
                                        class="w-12 h-12 object-contain"
                                    />
                                {:else}
                                    <div
                                        class="w-12 h-12 rounded-full bg-[color-mix(in_srgb,var(--text-color)_10%,transparent)] flex items-center justify-center text-2xl"
                                    >
                                        🏆
                                    </div>
                                {/if}
                            </div>

                            <span
                                class="text-xs font-bold text-[color-mix(in_srgb,var(--text-color)_20%,transparent)] text-center leading-tight line-clamp-2"
                            >
                                {achievement.name}
                            </span>

                            <div
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-44 bg-[var(--bg-color)] border border-[color-mix(in_srgb,var(--text-color)_12%,transparent)] rounded-xl p-3 text-xs pointer-events-none z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-200 shadow-xl"
                            >
                                <p
                                    class="font-bold text-[var(--text-color)] mb-1"
                                >
                                    {achievement.name}
                                </p>
                                {#if achievement.description}
                                    <p
                                        class="text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] leading-snug mb-2"
                                    >
                                        {achievement.description}
                                    </p>
                                {/if}
                                <p
                                    class="text-[color-mix(in_srgb,var(--text-color)_25%,transparent)] italic text-xs"
                                >
                                    🔒 Not yet unlocked
                                </p>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
            </div>
        {/if}
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
        box-shadow: 0 0 20px color-mix(in srgb, var(--primary-color) 25%, transparent);
    }
    .achievement-tooltip {
        transition: opacity 0.2s ease;
    }
</style>
