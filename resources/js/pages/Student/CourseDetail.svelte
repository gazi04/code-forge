<script>
    import { Link } from '@inertiajs/svelte';
    import Layout from '../../layouts/StudentLayout.svelte';

    export let course;
    export let world;
    export let lessons = [];

    $: worldData = world.data ?? world;
    $: themeData = worldData.theme;
    $: mapLayout = themeData?.config?.ui?.map_layout || 'linear';
</script>

<Layout theme={themeData}>
    <header class="mb-16 text-center relative z-10 pt-8">
        <Link
            href="/worlds/{worldData.slug}"
            class="absolute left-0 top-10 text-sm text-[var(--text-color)] opacity-40 hover:opacity-100 transition-opacity flex items-center gap-2"
        >
            <span class="text-lg">←</span>
            {worldData.name}
        </Link>

        <div
            class="inline-block px-3 py-1 mb-4 rounded-full border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-xs font-mono text-[var(--primary-color)] uppercase tracking-widest"
        >
            Active Curriculum
        </div>

        <h1
            class="text-4xl md:text-5xl font-black text-[var(--text-color)] tracking-tight drop-shadow-lg transition-colors duration-500"
        >
            {course.name}
        </h1>
    </header>

    <div class="map-viewport relative max-w-4xl mx-auto py-12">
        {#if mapLayout === 'linear' || mapLayout === 'branching'}
            <div
                class="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-1 bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] rounded-full z-0 shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_30%,transparent)] border-l border-r border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)]"
            ></div>

            <div class="flex flex-col items-center gap-12 relative z-10">
                {#each lessons as lesson, i}
                    {@const isBranching = mapLayout === 'branching'}
                    {@const alignLeft = isBranching && i % 2 === 0}

                    <div class="relative w-full flex justify-center group">
                        {#if isBranching}
                            <div
                                class="absolute top-1/2 -translate-y-1/2 w-1/4 h-px bg-gradient-to-r {alignLeft
                                    ? 'from-transparent to-[var(--primary-color)] right-1/2'
                                    : 'from-[var(--primary-color)] to-transparent left-1/2'} opacity-30 group-hover:opacity-100 transition-opacity z-0"
                            ></div>
                        {/if}

                        <Link
                            href="/lessons/{lesson.slug}"
                            class="relative z-10 flex items-center gap-6 {isBranching
                                ? alignLeft
                                    ? 'mr-auto ml-12 md:ml-24 flex-row'
                                    : 'ml-auto mr-12 md:mr-24 flex-row-reverse text-right'
                                : 'w-full max-w-md bg-surface p-4'} transition-all duration-300 hover:scale-[1.02] hover:border-[var(--primary-color)]"
                        >
                            <div
                                class="w-14 h-14 rounded-full bg-[var(--bg-color)] border-2 border-[var(--primary-color)] flex items-center justify-center shadow-[0_0_20px_color-mix(in_srgb,var(--primary-color)_40%,transparent)] group-hover:bg-[var(--primary-color)] transition-colors duration-300 flex-shrink-0"
                            >
                                <span
                                    class="font-bold text-lg text-[var(--primary-color)] group-hover:text-[var(--bg-color)] transition-colors"
                                    >{i + 1}</span
                                >
                            </div>

                            <div class="flex-1 {isBranching ? '' : 'ml-2'}">
                                <h3
                                    class="font-bold text-lg text-[var(--text-color)] opacity-90 group-hover:opacity-100 transition-opacity leading-tight"
                                >
                                    {lesson.name}
                                </h3>
                                <div
                                    class="text-xs font-mono text-[var(--text-color)] opacity-40 mt-1 uppercase tracking-wider"
                                >
                                    Quest Active
                                </div>
                            </div>
                        </Link>
                    </div>
                {/each}
            </div>
        {:else if mapLayout === 'road'}
            <div class="flex flex-col relative z-10 w-full px-4 space-y-16">
                {#each lessons as lesson, i}
                    {@const isLeft = i % 2 === 0}

                    <div
                        class="relative w-full flex {isLeft
                            ? 'justify-start'
                            : 'justify-end'} group"
                    >
                        {#if i !== lessons.length - 1}
                            <div
                                class="absolute top-1/2 h-[calc(100%+4rem)] border-[3px] border-dashed border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] -z-10
                left-[1.75rem] right-[1.75rem]
                {isLeft
                                    ? 'border-t-0 border-r-0 border-l border-b rounded-bl-[8rem]'
                                    : 'border-t-0 border-l-0 border-r border-b rounded-br-[8rem]'}"
                            ></div>
                        {/if}

                        <Link
                            href="/lessons/{lesson.slug}"
                            class="relative z-10 flex items-center gap-6 w-[90%] md:w-[75%] {isLeft
                                ? 'flex-row'
                                : 'flex-row-reverse text-right'} transition-all duration-300 hover:scale-[1.02]"
                        >
                            <div
                                class="w-14 h-14 rounded-full bg-[var(--bg-color)] border-2 border-[var(--primary-color)] flex items-center justify-center shadow-[0_0_20px_color-mix(in_srgb,var(--primary-color)_40%,transparent)] group-hover:bg-[var(--primary-color)] transition-colors duration-300 flex-shrink-0 z-20"
                            >
                                <span
                                    class="font-bold text-lg text-[var(--primary-color)] group-hover:text-[var(--bg-color)] transition-colors"
                                    >{i + 1}</span
                                >
                            </div>

                            <div
                                class="flex-1 bg-surface p-5 rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] group-hover:border-[var(--primary-color)] group-hover:shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-all duration-300"
                            >
                                <h3
                                    class="font-bold text-xl text-[var(--text-color)] opacity-90 group-hover:opacity-100 transition-opacity leading-tight"
                                >
                                    {lesson.name}
                                </h3>
                                <div
                                    class="text-xs font-mono text-[var(--text-color)] opacity-40 mt-2 uppercase tracking-wider"
                                >
                                    Quest Active
                                </div>
                            </div>
                        </Link>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</Layout>
