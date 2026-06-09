<script>
    let { hero } = $props();

    let xpInCurrentLevel = $derived(hero.xp - hero.xp_for_current_level);
    let xpNeededForNextLevel = $derived(
        hero.xp_for_next_level - hero.xp_for_current_level,
    );
    let xpPercent = $derived(
        Math.min(100, Math.round((xpInCurrentLevel / xpNeededForNextLevel) * 100)),
    );
    let xpToNext = $derived(hero.xp_for_next_level - hero.xp);

    function getTitle(level) {
        if (level < 5) return 'Novice';
        if (level < 10) return 'Apprentice';
        if (level < 20) return 'Logic Adept';
        return 'Master Hacker';
    }
</script>

<div class="bg-surface rounded-2xl p-8 mb-6 relative overflow-hidden">
    <div
        class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] to-transparent pointer-events-none"
    ></div>

    <div class="relative flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <!-- Avatar with glow -->
        <div class="relative shrink-0">
            <div
                class="absolute inset-0 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] blur-xl opacity-35 scale-125 pointer-events-none transition-colors duration-500"
            ></div>
            <div class="relative w-24 h-24">
                {#if hero.equipped?.avatar?.image_url}
                    <img
                        src={hero.equipped.avatar.image_url}
                        alt={hero.name}
                        class="absolute inset-0 w-full h-full rounded-full object-cover shadow-[0_0_40px_color-mix(in_srgb,var(--primary-color)_40%,transparent)]"
                    />
                {:else}
                    <div
                        class="absolute inset-0 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-black font-black text-4xl shadow-[0_0_40px_color-mix(in_srgb,var(--primary-color)_40%,transparent)] transition-colors duration-500"
                    >
                        {hero.name.charAt(0)}
                    </div>
                {/if}
            </div>
        </div>

        <!-- Identity + XP -->
        <div class="flex-1 w-full text-center sm:text-left">
            <div
                class="mb-2 flex items-center justify-center sm:justify-start gap-2 flex-wrap"
            >
                <h1 class="text-2xl font-black text-[var(--text-color)] tracking-tight">
                    {hero.name}
                </h1>
                {#if hero.equipped?.title}
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                        style="color: {hero.equipped.title.color ?? 'var(--text-color)'}; border-color: {hero.equipped.title.color ?? 'var(--text-color)'}; background-color: color-mix(in srgb, {hero.equipped.title.color ?? 'var(--text-color)'} 12%, transparent);"
                    >
                        {hero.equipped.title.name}
                    </span>
                {:else}
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-[color-mix(in_srgb,var(--accent-color)_12%,transparent)] border border-[color-mix(in_srgb,var(--accent-color)_35%,transparent)] text-[var(--accent-color)] transition-colors duration-500"
                    >
                        {getTitle(hero.level)}
                    </span>
                {/if}
            </div>

            <p
                class="text-xs font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] uppercase tracking-widest mb-5"
            >
                Level {hero.level} Agent
            </p>

            <div
                class="mb-1.5 flex items-center justify-between text-xs font-mono"
            >
                <span
                    class="text-[color-mix(in_srgb,var(--text-color)_45%,transparent)]"
                    >{xpPercent}% to Lv. {hero.level + 1}</span
                >
                <span
                    class="text-[color-mix(in_srgb,var(--primary-color)_80%,transparent)] transition-colors duration-500"
                >
                    {xpToNext.toLocaleString()} XP left
                </span>
            </div>
            <div
                class="w-full h-2.5 rounded-full overflow-hidden bg-[color-mix(in_srgb,var(--text-color)_8%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_5%,transparent)]"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-[var(--primary-color)] to-[var(--accent-color)] transition-all duration-700 shadow-[0_0_8px_color-mix(in_srgb,var(--primary-color)_60%,transparent)]"
                    style="width: {xpPercent}%"
                ></div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="relative mt-6 grid grid-cols-3 gap-3">
        <div
            class="flex flex-col items-center gap-1.5 px-3 py-4 rounded-xl bg-[color-mix(in_srgb,var(--primary-color)_7%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-500"
        >
            <span class="text-lg leading-none">✨</span>
            <span
                class="text-base font-black text-[var(--primary-color)] font-mono transition-colors duration-500"
                >{hero.xp.toLocaleString()}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]"
                >Total XP</span
            >
        </div>

        <div
            class="flex flex-col items-center gap-1.5 px-3 py-4 rounded-xl bg-yellow-400/5 border border-yellow-400/20"
        >
            <span class="text-lg leading-none">💰</span>
            <span class="text-base font-black text-yellow-400 font-mono"
                >{hero.coins.toLocaleString()}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]"
                >Coins</span
            >
        </div>

        <div
            class="flex flex-col items-center gap-1.5 px-3 py-4 rounded-xl bg-orange-400/5 border border-orange-400/20"
        >
            <span class="text-lg leading-none">🔥</span>
            <span class="text-base font-black text-orange-400 font-mono"
                >{hero.streak_count}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]"
                >Day Streak</span
            >
        </div>
    </div>
</div>
