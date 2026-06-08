<script>
    let { hero } = $props();

    let xpInCurrentLevel = $derived(hero.xp - hero.xp_for_current_level);
    let xpNeededForNextLevel = $derived(
        hero.xp_for_next_level - hero.xp_for_current_level,
    );
    let xpPercent = $derived(
        Math.min(
            100,
            Math.round((xpInCurrentLevel / xpNeededForNextLevel) * 100),
        ),
    );

    function getTitle(level) {
        if (level < 5) return 'Novice';
        if (level < 10) return 'Apprentice';
        if (level < 20) return 'Logic Adept';
        return 'Master Hacker';
    }
</script>

<div class="bg-surface rounded-2xl p-8 mb-6">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
        <div
            class="w-20 h-20 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-black font-black text-3xl shadow-inner shrink-0 transition-colors duration-500"
        >
            {hero.name.charAt(0)}
        </div>

        <div class="flex-1 w-full text-center md:text-left">
            <h1 class="text-2xl font-black text-white mb-0.5">{hero.name}</h1>
            <p
                class="text-xs font-mono uppercase tracking-widest text-white/40 mb-4"
            >
                {getTitle(hero.level)}
            </p>

            <div
                class="mb-2 flex items-center justify-between text-xs font-mono text-white/50"
            >
                <span>Level {hero.level}</span>
                <span>{hero.xp} / {hero.xp_for_next_level} XP</span>
            </div>
            <div
                class="w-full h-3 bg-black/40 rounded-full overflow-hidden border border-white/5"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-[var(--primary-color)] to-[var(--accent-color)] transition-all duration-700"
                    style="width: {xpPercent}%"
                ></div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-3">
        <div
            class="flex flex-col items-center gap-1 px-3 py-3 rounded-xl bg-black/30 border border-white/5"
        >
            <span class="text-lg leading-none">✨</span>
            <span
                class="text-sm font-black text-[var(--primary-color)] font-mono transition-colors duration-500"
                >{hero.xp}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-white/30"
                >XP</span
            >
        </div>

        <div
            class="flex flex-col items-center gap-1 px-3 py-3 rounded-xl bg-black/30 border border-white/5"
        >
            <span class="text-lg leading-none">💰</span>
            <span class="text-sm font-black text-yellow-400 font-mono"
                >{hero.coins}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-white/30"
                >Coins</span
            >
        </div>

        <div
            class="flex flex-col items-center gap-1 px-3 py-3 rounded-xl bg-black/30 border border-white/5"
        >
            <span class="text-lg leading-none">🔥</span>
            <span class="text-sm font-black text-orange-400 font-mono"
                >{hero.streak_count}</span
            >
            <span
                class="text-[10px] font-mono uppercase tracking-widest text-white/30"
                >Streak</span
            >
        </div>
    </div>
</div>
