<script>
    import { Link, page } from '@inertiajs/svelte';

    let { user } = $props();

    let isLeaderboard = $derived(page.url.startsWith('/leaderboard'));
</script>

<nav
    class="sticky top-0 z-50 border-b border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] bg-[color-mix(in_srgb,var(--bg-color)_75%,transparent)] backdrop-blur-2xl text-[var(--text-color)] transition-colors duration-500"
>
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <Link
                href="/worlds"
                class="font-black tracking-widest uppercase text-sm text-[var(--text-color)] opacity-80 hover:text-[var(--primary-color)] hover:opacity-100 transition-colors drop-shadow-md"
            >
                Arcane.dev
            </Link>
        </div>

        <div
            class="flex items-center gap-2 md:gap-3 text-xs font-bold uppercase tracking-wider hidden sm:flex"
        >
            {#if user}
                {#if user.streak_count > 0}
                    <div
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-orange-400 font-mono shadow-[0_0_10px_rgba(249,115,22,0.1)]"
                    >
                        <span class="text-sm leading-none">🔥</span>
                        {user.streak_count}
                    </div>
                {/if}

                <div
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 font-mono shadow-[0_0_10px_rgba(234,179,8,0.1)]"
                >
                    <span class="text-sm leading-none">💰</span>
                    {user.coins}
                </div>

                <div
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] text-[var(--primary-color)] font-mono shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-500"
                >
                    <span class="text-sm leading-none">✨</span>
                    {user.xp} XP
                </div>

                <div
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--accent-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--accent-color)_40%,transparent)] text-[var(--accent-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--accent-color)_20%,transparent)] transition-colors duration-500"
                >
                    Lv. {user.level}
                </div>

                <Link
                    href="/leaderboard"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono border transition-colors duration-500
                        {isLeaderboard
                            ? 'bg-amber-500/15 border-amber-400/40 text-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.2)]'
                            : 'bg-amber-500/5 border-amber-500/15 text-amber-400/60 hover:bg-amber-500/10 hover:border-amber-400/30 hover:text-amber-400'}"
                >
                    <span class="text-sm leading-none">🏆</span>
                </Link>

                <Link
                    href="/profile"
                    class="flex items-center gap-2 pl-3 ml-2 border-l border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:opacity-80 transition-opacity"
                >
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-black font-black text-sm shadow-inner transition-colors duration-500"
                    >
                        {user.name.charAt(0)}
                    </div>
                    <span
                        class="text-[var(--text-color)] opacity-80 hidden md:block"
                        >{user.name}</span
                    >
                </Link>
            {:else}
                <div
                    class="px-3 py-1 rounded-md bg-white/5 border border-white/10 flex items-center gap-2"
                >
                    <span class="opacity-50">System</span>
                    <span
                        class="text-[var(--accent-color)] transition-colors duration-500"
                        >Online</span
                    >
                </div>
            {/if}
        </div>
    </div>
</nav>
