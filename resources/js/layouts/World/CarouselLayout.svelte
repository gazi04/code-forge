<script>
    import { Link } from '@inertiajs/svelte';

    export let courses = [];

    let container;
    let activeIndex = 0;

    // Reset slider position when transitioning to a different world's courses
    $: if (courses) {
        activeIndex = 0;

        if (container) {
            container.scrollLeft = 0;
        }
    }

    // Programmatic navigation utilizing the native browser engine layout engine
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

    // Listens to manual scroll/swipe events to dynamically update active dot state
    function handleScroll() {
        if (!container) {
            return;
        }

        const containerCenter =
            container.scrollLeft + container.offsetWidth / 2;
        let closestIndex = 0;
        let minDistance = Infinity;

        // Evaluate which child layout card is closest to the horizontal viewport center
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

<div class="w-full pb-24 group/carousel">
    <div class="relative px-4 sm:px-12 md:px-16">
        {#if courses.length > 1}
            <button
                onclick={() => scrollToIdx(activeIndex - 1)}
                disabled={activeIndex === 0}
                class="absolute left-0 top-1/2 -translate-y-1/2 z-30 w-10 h-10 md:w-12 md:h-12 rounded-full border border-[color-mix(in_srgb,var(--text-color)_15%,transparent)] bg-[color-mix(in_srgb,var(--surface)_80%,transparent)] text-[var(--text-color)] backdrop-blur-md flex items-center justify-center shadow-2xl transition-all duration-300
          disabled:opacity-0 disabled:pointer-events-none hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] hover:scale-110 active:scale-95"
                aria-label="Previous Course"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="w-5 h-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 19.5L8.25 12l7.5-7.5"
                    />
                </svg>
            </button>
        {/if}

        {#if courses.length > 1}
            <button
                onclick={() => scrollToIdx(activeIndex + 1)}
                disabled={activeIndex === courses.length - 1}
                class="absolute right-0 top-1/2 -translate-y-1/2 z-30 w-10 h-10 md:w-12 md:h-12 rounded-full border border-[color-mix(in_srgb,var(--text-color)_15%,transparent)] bg-[color-mix(in_srgb,var(--surface)_80%,transparent)] text-[var(--text-color)] backdrop-blur-md flex items-center justify-center shadow-2xl transition-all duration-300
          disabled:opacity-0 disabled:pointer-events-none hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] hover:scale-110 active:scale-95"
                aria-label="Next Course"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="w-5 h-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8.25 4.5l7.5 7.5-7.5 7.5"
                    />
                </svg>
            </button>
        {/if}

        <div
            bind:this={container}
            onscroll={handleScroll}
            class="flex overflow-x-auto snap-x snap-mandatory gap-6 hide-scrollbar w-full scroll-smooth py-2"
        >
            {#each courses as course, i}
                <Link
                    href="/course/{course.slug}"
                    class="snap-center shrink-0 w-[calc(100vw-4rem)] sm:w-[350px] md:w-[400px] group flex flex-col p-8 bg-surface border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:shadow-[0_0_30px_color-mix(in_srgb,var(--primary-color)_15%,transparent)] transition-all duration-300 rounded-2xl shadow-xl min-h-[260px]"
                >
                    <span
                        class="text-xs font-mono text-[var(--primary-color)] mb-4 block uppercase tracking-widest font-bold"
                    >
                        Sector {String(i + 1).padStart(2, '0')}
                    </span>

                    <h3
                        class="text-2xl font-black text-[var(--text-color)] mb-3 group-hover:text-[var(--primary-color)] tracking-wide transition-colors duration-300"
                    >
                        {course.name}
                    </h3>

                    <p
                        class="text-sm text-[var(--text-color)] opacity-60 line-clamp-3 mb-6"
                    >
                        {course.description ||
                            'Initialize this sector checkpoint matrix to unlock its specific algorithmic challenges.'}
                    </p>

                    <div
                        class="mt-auto pt-4 border-t border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex items-center gap-2 text-[var(--text-color)] opacity-50 group-hover:opacity-100 group-hover:text-[var(--primary-color)] transition-all duration-300"
                    >
                        <span
                            class="text-xs font-mono uppercase tracking-widest font-bold"
                            >Enter Simulation Module →</span
                        >
                    </div>
                </Link>
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
                        <span
                            class="absolute inset-0 rounded-full bg-[var(--primary-color)] animate-ping opacity-25"
                        ></span>
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
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
