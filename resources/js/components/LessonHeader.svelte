<script>
    import { Link } from '@inertiajs/svelte';

    let { lesson, course_slug, is_completed = false } = $props();
</script>

<header
    class="sticky top-14 z-40 mb-8 max-w-4xl mx-auto px-6 py-4 rounded-xl bg-[var(--surface-color)] backdrop-blur-md border border-[color-mix(in_srgb,var(--primary-color)_25%,transparent)] shadow-[0_4px_32px_color-mix(in_srgb,var(--primary-color)_8%,transparent)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-colors duration-800"
>
    <div class="flex items-center gap-4">
        <Link
            href="/course/{course_slug}"
            class="w-10 h-10 rounded-md bg-surface flex items-center justify-center border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-inner hover:border-[var(--primary-color)] transition-colors group"
        >
            <span class="text-xl group-hover:scale-110 transition-transform">↩️</span>
        </Link>

        <div>
            <h1 class="text-xl font-bold tracking-wide text-[var(--text-color)]">
                {lesson.name}
            </h1>
            <p class="text-xs text-[var(--text-color)] opacity-50 uppercase tracking-wider">
                Estimated: {lesson.estimated_duration}m
            </p>
        </div>
    </div>

    {#if is_completed}
        <div class="flex items-center gap-2 px-4 py-1.5 rounded-md bg-emerald-500/10 border border-emerald-500/40 text-emerald-400 shadow-[0_0_10px_rgba(16,185,129,0.15)]">
            <span>✓</span>
            <span class="text-sm font-black uppercase tracking-widest">Lesson Completed</span>
        </div>
    {:else}
        <div class="flex gap-3 text-sm font-medium">
            <div
                class="bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] px-4 py-1.5 rounded-md border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] text-[var(--primary-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-800"
            >
                ✨ {lesson.xp_reward} XP
            </div>
            <div
                class="bg-[color-mix(in_srgb,var(--accent-color)_10%,transparent)] px-4 py-1.5 rounded-md border border-[color-mix(in_srgb,var(--accent-color)_30%,transparent)] text-[var(--accent-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--accent-color)_20%,transparent)] transition-colors duration-800"
            >
                💰 {lesson.coin_reward}
            </div>
        </div>
    {/if}
</header>
