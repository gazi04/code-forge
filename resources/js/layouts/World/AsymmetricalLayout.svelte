<script>
    import { Link } from '@inertiajs/svelte';
    let { courses = [], userLevel = 1 } = $props();
</script>

<div class="relative max-w-4xl mx-auto pb-24 py-8">
    <div
        class="absolute left-4 md:left-1/2 top-0 bottom-0 w-px bg-gradient-to-b from-[var(--primary-color)] via-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] to-transparent opacity-50 -translate-x-1/2"
    ></div>

    <div class="flex flex-col gap-12 md:gap-24">
        {#each courses as course, i (course.slug)}
            {#if userLevel >= (course.min_level_requirement || 1)}
                {@const isEven = i % 2 === 0}

                <div
                    class="relative flex flex-col md:flex-row items-center w-full {isEven
                        ? 'md:justify-start'
                        : 'md:justify-end'}"
                >
                    <div
                        class="absolute left-4 md:left-1/2 w-4 h-4 rounded-full bg-[var(--bg-color)] border-4 border-[var(--primary-color)] shadow-[0_0_15px_var(--primary-color)] -translate-x-1/2 z-20"
                    ></div>

                    <Link
                        href="/course/{course.slug}"
                        class="ml-10 md:ml-0 w-[calc(100%-2.5rem)] md:w-[45%] group relative p-6 bg-surface border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:shadow-[0_0_30px_color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-all duration-300 rounded-2xl"
                    >
                        <div
                            class="text-[10px] font-black uppercase tracking-widest text-[var(--primary-color)] mb-2"
                        >
                            Waypoint {i + 1}
                        </div>
                        <h3
                            class="text-lg font-bold text-[var(--text-color)] mb-2 group-hover:text-[var(--primary-color)] transition-colors"
                        >
                            {course.name}
                        </h3>
                        <p
                            class="text-xs text-[var(--text-color)] opacity-60 line-clamp-2"
                        >
                            Advance to this coordinate to continue the journey.
                        </p>
                    </Link>
                </div>
            {:else}
                <div
                    class="absolute left-4 md:left-1/2 w-4 h-4 rounded-full bg-[var(--bg-color)] border-4 border-red-900/40 shadow-[0_0_15px_rgba(239,68,68,0.15)] -translate-x-1/2 z-20 grayscale"
                ></div>

                <div
                    class="ml-10 md:ml-0 w-[calc(100%-2.5rem)] md:w-[45%] relative p-6 bg-black/40 border border-red-900/20 rounded-2xl grayscale opacity-60 cursor-not-allowed"
                >
                    <div
                        class="text-[10px] font-black uppercase tracking-widest text-red-500/70 mb-2 flex items-center gap-1.5"
                    >
                        🔒 Sector Locked
                    </div>
                    <h3
                        class="text-lg font-bold text-[var(--text-color)] opacity-40 mb-2"
                    >
                        {course.name}
                    </h3>
                    <p
                        class="text-xs text-[var(--text-color)] opacity-30 line-clamp-2"
                    >
                        Requires Access Clearance: Level {course.min_level_requirement}
                    </p>
                </div>
            {/if}
        {/each}
    </div>
</div>
