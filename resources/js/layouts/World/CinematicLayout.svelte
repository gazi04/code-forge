<script>
    import { Link } from '@inertiajs/svelte';
    let { courses = [], userLevel = 1 } = $props();
</script>

<div
    class="h-[70dvh] md:h-[75vh] w-full overflow-y-auto snap-y snap-mandatory hide-scrollbar rounded-3xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl relative"
>
    <div
        class="absolute top-6 right-6 z-20 animate-pulse text-[10px] uppercase tracking-widest font-mono text-[var(--text-color)] opacity-50"
    >
        ↓ Scroll to navigate
    </div>

    {#each courses as course, i (course.slug)}
        {#if userLevel >= (course.min_level_requirement || 1)}
            <div
                class="h-full w-full snap-center shrink-0 flex items-center justify-center p-5 sm:p-8 md:p-16 relative overflow-hidden group"
            >
                <div
                    class="absolute inset-0 bg-gradient-to-br from-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] to-[color-mix(in_srgb,var(--bg-color)_90%,transparent)] opacity-50 group-hover:opacity-100 transition-opacity duration-700"
                ></div>

                <Link
                    href="/course/{course.slug}"
                    class="relative z-10 w-full max-w-3xl text-center flex flex-col items-center"
                >
                    <span
                        class="text-4xl sm:text-6xl md:text-8xl font-black text-[var(--text-color)] opacity-5 mb-4 group-hover:scale-110 transition-transform duration-700"
                        >{String(i + 1).padStart(2, '0')}</span
                    >
                    <h3
                        class="text-3xl sm:text-4xl md:text-5xl font-black text-[var(--text-color)] mb-6 drop-shadow-lg group-hover:text-[var(--primary-color)] transition-colors duration-500"
                    >
                        {course.name}
                    </h3>
                    <div
                        class="px-6 py-3.5 sm:px-8 sm:py-3 rounded-full border border-[var(--primary-color)] text-[var(--primary-color)] text-sm font-bold uppercase tracking-widest hover:bg-[var(--primary-color)] hover:text-[var(--bg-color)] active:bg-[var(--primary-color)] active:text-[var(--bg-color)] active:scale-[0.98] transition-colors duration-300 cursor-pointer shadow-[0_0_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]"
                    >
                        Begin Sequence
                    </div>
                </Link>
            </div>
        {:else}
            <div
                class="h-full w-full snap-center shrink-0 flex items-center justify-center p-5 sm:p-8 md:p-16 relative overflow-hidden bg-black/30 mix-blend-luminosity opacity-50 cursor-not-allowed"
            >
                <div
                    class="absolute inset-0 bg-gradient-to-br from-red-950/10 to-transparent opacity-40"
                ></div>

                <div
                    class="relative z-10 w-full max-w-3xl text-center flex flex-col items-center"
                >
                    <span
                        class="text-5xl md:text-6xl mb-4 select-none filter drop-shadow-[0_0_10px_rgba(239,68,68,0.3)]"
                    >
                        🔒
                    </span>
                    <h3
                        class="text-3xl sm:text-4xl md:text-5xl font-black text-[var(--text-color)] opacity-20 mb-4 tracking-tight"
                    >
                        {course.name}
                    </h3>
                    <p
                        class="text-sm font-mono uppercase tracking-widest text-red-500/80 font-bold max-w-md bg-red-950/20 border border-red-900/30 px-6 py-2 rounded-xl backdrop-blur-sm"
                    >
                        Access Denied: Requires Level {course.min_level_requirement}
                    </p>
                </div>
            </div>
        {/if}
    {/each}
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
