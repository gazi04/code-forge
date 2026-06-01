<script>
    import { Link } from '@inertiajs/svelte';

    let { courses = [], userLevel = 1 } = $props();

    let container;
    let activeIndex = $state(0); // Upgraded to Svelte 5 state tracker

    // 2. Svelte 5 Side Effect (Replaces the broken legacy '$:' tracking)
    $effect(() => {
        if (courses) {
            activeIndex = 0;
            if (container) {
                container.scrollLeft = 0;
            }
        }
    });

    function scrollToIdx(idx) {
        if (idx < 0 || idx >= courses.length || !container) {
            return;
        }
        const targetChild = container.children[idx];
        if (targetChild) {
            targetChild.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center',
            });
            activeIndex = idx;
        }
    }

    function handleScroll() {
        if (!container) {
            return;
        }
        const containerCenter = container.scrollLeft + container.offsetWidth / 2;
        let closestIndex = 0;
        let minDistance = Infinity;

        Array.from(container.children).forEach((child, idx) => {
            const childCenter = child.offsetLeft + child.offsetWidth / 2;
            const distance = Math.abs(containerCenter - childCenter);
            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = idx;
            }
        });
        activeIndex = closestIndex;
    }
</script>

<div class="relative w-full max-w-5xl mx-auto py-4">
    <div
        bind:this={container}
        onscroll={handleScroll}
        class="flex gap-6 overflow-x-auto snap-x snap-mandatory hide-scrollbar px-6 py-4 scroll-smooth"
    >
        {#each courses as course, i}
            {#if userLevel >= (course.min_level_requirement || 1)}
                <Link
                    href="/course/{course.slug}"
                    class="w-[350px] sm:w-[400px] shrink-0 group relative flex flex-col p-8 bg-surface border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] transition-all duration-500 rounded-2xl shadow-xl scroll-mx-6"
                >
                    <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity text-8xl font-black text-[var(--text-color)] select-none">
                        {i + 1}
                    </div>
                    <div class="relative z-10 flex flex-col h-full">
                        <h3 class="text-2xl font-black text-[var(--text-color)] tracking-tight mb-4 group-hover:text-[var(--primary-color)] transition-colors duration-300">
                            {course.name}
                        </h3>
                        <p class="text-sm text-[var(--text-color)] opacity-60 leading-relaxed line-clamp-4 mb-8 flex-1">
                            {course.description || 'Initialize the sequence to discover what lies within this sector.'}
                        </p>
                        <div class="mt-auto pt-4 border-t border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-[var(--primary-color)] transition-all duration-300">
                            <span class="text-xs font-mono uppercase tracking-widest font-bold">Enter Simulation Module →</span>
                        </div>
                    </div>
                </Link>
            {:else}
                <div
                    class="w-[350px] sm:w-[400px] shrink-0 relative flex flex-col p-8 bg-black/40 border border-red-900/20 rounded-2xl shadow-none grayscale opacity-70 cursor-not-allowed scroll-mx-6"
                >
                    <div class="absolute top-4 right-4 text-2xl opacity-30">
                        🔒
                    </div>
                    <div class="relative z-10 flex flex-col h-full">
                        <h3 class="text-2xl font-black text-[var(--text-color)] opacity-40 tracking-tight mb-4">
                            {course.name}
                        </h3>
                        <p class="text-sm text-[var(--text-color)] opacity-30 leading-relaxed line-clamp-4 mb-8 flex-1">
                            This modular sector is encrypted. Complete previous coordinates to gain level authorization.
                        </p>
                        <div class="mt-auto pt-4 border-t border-red-900/20 text-red-500 font-bold font-mono text-xs uppercase tracking-widest">
                            Requires Level {course.min_level_requirement}
                        </div>
                    </div>
                </div>
            {/if}
        {/each}
    </div>
</div>

{#if courses.length > 1}
    <div class="flex items-center justify-center gap-2.5 mt-8 w-full">
        {#each courses as _, idx}
            {@const isActive = activeIndex === idx}
            <button
                onclick={() => scrollToIdx(idx)}
                class="h-2 rounded-full transition-all duration-500 relative
                {isActive
                    ? 'w-8 bg-[var(--primary-color)] shadow-[0_0_12px_var(--primary-color)]'
                    : 'w-2 bg-[var(--text-color)] opacity-20 hover:opacity-50'}"
                aria-label="Navigate to sector index position {idx + 1}"
            >
                {#if isActive}
                    <span class="absolute inset-0 rounded-full bg-[var(--primary-color)] animate-ping opacity-25"></span>
                {/if}
            </button>
        {/each}
    </div>
{/if}

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
