<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import SearchModal from '@/components/SearchModal.svelte';
    import StudentBottomNav from '@/components/StudentBottomNav.svelte';
    import { logout } from '@/routes';
    import { leaderboard, profile } from '@/routes/student';
    import { index as storeIndex } from '@/routes/student/store';
    import { index as worldsIndex } from '@/routes/student/world';

    let { user } = $props();

    let isLeaderboard = $derived(page.url.startsWith('/leaderboard'));
    let isStore = $derived(page.url.startsWith('/store'));
    let isProfile = $derived(page.url.startsWith('/profile'));

    let searchOpen = $state(false);

    $effect(() => {
        if (!user) {
return;
}

        function handleKeydown(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchOpen = true;
            }
        }

        window.addEventListener('keydown', handleKeydown);

        return () => window.removeEventListener('keydown', handleKeydown);
    });
</script>

<nav
    class="sticky top-0 z-50 border-b border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] bg-[color-mix(in_srgb,var(--bg-color)_80%,transparent)] backdrop-blur-2xl text-[var(--text-color)] transition-colors duration-500"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

        <!-- Logo -->
        <Link
            href={worldsIndex.url()}
            class="font-black tracking-widest uppercase text-sm text-[var(--text-color)] opacity-70 hover:text-[var(--primary-color)] hover:opacity-100 transition-colors shrink-0"
        >
            Arcane.dev
        </Link>

        {#if user}
            <!-- Search trigger -->
            <button
                onclick={() => (searchOpen = true)}
                class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg text-xs border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_3%,transparent)] text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:text-[var(--text-color)] hover:border-[color-mix(in_srgb,var(--text-color)_15%,transparent)] transition-colors duration-200 flex-1 max-w-56"
            >
                <span class="text-sm leading-none">🔍</span>
                <span class="hidden md:inline font-mono text-[10px] uppercase tracking-widest flex-1 text-left">Search</span>
                <kbd class="hidden lg:inline text-[9px] px-1.5 py-0.5 rounded border border-[color-mix(in_srgb,var(--text-color)_12%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_4%,transparent)] font-mono shrink-0">⌘K</kbd>
            </button>

            <!-- Right side -->
            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">

                <!-- Stats -->
                <div class="flex items-center gap-2 px-2 py-1.5 lg:gap-3 lg:px-3 rounded-lg bg-[color-mix(in_srgb,var(--text-color)_4%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_7%,transparent)] font-mono text-xs">
                    <span class="text-[var(--accent-color)] font-black transition-colors duration-500">Lv. {user.level}</span>
                    <span class="text-[color-mix(in_srgb,var(--text-color)_15%,transparent)]">·</span>
                    <span class="text-yellow-400 font-bold">💰 {user.coins.toLocaleString()}</span>
                    {#if user.streak_count > 0}
                        <span class="text-[color-mix(in_srgb,var(--text-color)_15%,transparent)]">·</span>
                        {#if user.streak_at_risk}
                            <span class="text-red-400 font-bold animate-pulse">⚠️ {user.streak_count} 🔥</span>
                        {:else}
                            <span class="text-orange-400 font-bold">🔥 {user.streak_count}</span>
                        {/if}
                    {/if}
                </div>

                <!-- Divider -->
                <div class="hidden lg:block w-px h-6 bg-[color-mix(in_srgb,var(--text-color)_10%,transparent)] mx-1"></div>

                <!-- Nav links -->
                <Link
                    href={leaderboard.url()}
                    class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wide border transition-colors duration-200
                        {isLeaderboard
                            ? 'bg-amber-500/12 border-amber-400/35 text-amber-400'
                            : 'border-transparent text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:text-[var(--text-color)]'}"
                >
                    <span class="text-base leading-none">🏆</span>
                    <span class="hidden sm:inline">Leaderboard</span>
                </Link>

                <Link
                    href={storeIndex.url()}
                    class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wide border transition-colors duration-200
                        {isStore
                            ? 'bg-[color-mix(in_srgb,var(--primary-color)_12%,transparent)] border-[color-mix(in_srgb,var(--primary-color)_35%,transparent)] text-[var(--primary-color)]'
                            : 'border-transparent text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:text-[var(--text-color)]'}"
                >
                    <span class="text-base leading-none">🛍️</span>
                    <span class="hidden sm:inline">Store</span>
                </Link>

                <!-- Divider -->
                <div class="hidden md:block w-px h-6 bg-[color-mix(in_srgb,var(--text-color)_10%,transparent)] mx-1"></div>

                <!-- Profile -->
                <Link
                    href={profile.url()}
                    class="hidden md:flex items-center gap-2 px-2 py-1.5 rounded-lg border transition-colors duration-200
                        {isProfile
                            ? 'bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] border-[color-mix(in_srgb,var(--text-color)_12%,transparent)]'
                            : 'border-transparent hover:bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_10%,transparent)]'}"
                >
                    <div class="relative shrink-0 w-8 h-8">
                        {#if user.equipped?.avatar?.image_url}
                            <img
                                src={user.equipped.avatar.image_url}
                                alt={user.name}
                                class="absolute inset-0 w-full h-full rounded-full object-cover"
                            />
                        {:else}
                            <div
                                class="absolute inset-0 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-black font-black text-sm transition-colors duration-500"
                            >
                                {user.name.charAt(0)}
                            </div>
                        {/if}
                    </div>
                    <div class="hidden md:flex flex-col leading-tight min-w-0">
                        <span class="text-xs font-bold text-[var(--text-color)] truncate max-w-[100px]">{user.name}</span>
                        {#if user.equipped?.title}
                            <span class="text-[10px] font-mono truncate max-w-[100px]" style="color: {user.equipped.title.color ?? 'inherit'}">{user.equipped.title.name}</span>
                        {/if}
                    </div>
                </Link>

                <!-- Logout -->
                <button
                    onclick={() => router.post(logout.url())}
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-black uppercase tracking-wide border border-transparent text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] hover:text-red-400 hover:bg-red-400/5 hover:border-red-400/20 active:text-red-400 active:bg-red-400/5 transition-colors duration-200"
                >
                    <span class="text-base leading-none">↩</span>
                    <span class="hidden sm:inline">Log out</span>
                </button>
            </div>

        {:else}
            <div class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 flex items-center gap-2 text-xs font-mono">
                <span class="opacity-40">System</span>
                <span class="text-[var(--accent-color)] transition-colors duration-500">Online</span>
            </div>
        {/if}

    </div>
</nav>

{#if user}
    <StudentBottomNav {user} onsearch={() => (searchOpen = true)} />
{/if}

{#if searchOpen}
    <SearchModal onclose={() => (searchOpen = false)} />
{/if}
