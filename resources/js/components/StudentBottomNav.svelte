<script>
    import { Link, page } from '@inertiajs/svelte';
    import { leaderboard, profile } from '@/routes/student';
    import { index as storeIndex } from '@/routes/student/store';
    import { index as worldsIndex } from '@/routes/student/world';

    let { user, onsearch } = $props();

    let isWorlds = $derived(
        ['/worlds', '/course', '/lessons'].some((prefix) =>
            page.url.startsWith(prefix),
        ),
    );
    let isLeaderboard = $derived(page.url.startsWith('/leaderboard'));
    let isStore = $derived(page.url.startsWith('/store'));
    let isProfile = $derived(page.url.startsWith('/profile'));

    const tabClass =
        'flex flex-col items-center justify-center gap-0.5 transition-colors duration-200 active:bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)]';
    const activeClass = 'text-[var(--primary-color)]';
    const inactiveClass =
        'text-[color-mix(in_srgb,var(--text-color)_45%,transparent)]';
</script>

<nav
    class="fixed bottom-0 inset-x-0 z-50 md:hidden border-t border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] bg-[color-mix(in_srgb,var(--bg-color)_85%,transparent)] backdrop-blur-2xl pb-[env(safe-area-inset-bottom)] transition-colors duration-500"
>
    <div class="grid grid-cols-5 h-16">
        <Link
            href={worldsIndex.url()}
            class="{tabClass} {isWorlds ? activeClass : inactiveClass}"
        >
            <span class="text-xl leading-none">🌍</span>
            <span class="text-[10px] font-black uppercase tracking-wide">Worlds</span>
        </Link>

        <button
            type="button"
            onclick={onsearch}
            class="{tabClass} {inactiveClass}"
        >
            <span class="text-xl leading-none">🔍</span>
            <span class="text-[10px] font-black uppercase tracking-wide">Search</span>
        </button>

        <Link
            href={leaderboard.url()}
            class="{tabClass} {isLeaderboard ? activeClass : inactiveClass}"
        >
            <span class="text-xl leading-none">🏆</span>
            <span class="text-[10px] font-black uppercase tracking-wide">Ranks</span>
        </Link>

        <Link
            href={storeIndex.url()}
            class="{tabClass} {isStore ? activeClass : inactiveClass}"
        >
            <span class="text-xl leading-none">🛍️</span>
            <span class="text-[10px] font-black uppercase tracking-wide">Store</span>
        </Link>

        <Link
            href={profile.url()}
            class="{tabClass} {isProfile ? activeClass : inactiveClass}"
        >
            <div class="relative w-6 h-6">
                {#if user.equipped?.avatar?.image_url}
                    <img
                        src={user.equipped.avatar.image_url}
                        alt={user.name}
                        class="absolute inset-0 w-full h-full rounded-full object-cover {isProfile
                            ? 'ring-2 ring-[var(--primary-color)]'
                            : ''}"
                    />
                {:else}
                    <div
                        class="absolute inset-0 rounded-full bg-gradient-to-tr from-[var(--primary-color)] to-[var(--accent-color)] flex items-center justify-center text-black font-black text-[10px] {isProfile
                            ? 'ring-2 ring-[var(--primary-color)]'
                            : ''}"
                    >
                        {user.name.charAt(0)}
                    </div>
                {/if}
            </div>
            <span class="text-[10px] font-black uppercase tracking-wide">Profile</span>
        </Link>
    </div>
</nav>
