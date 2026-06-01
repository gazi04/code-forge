<script>
    import { Link } from '@inertiajs/svelte';
    import Layout from '../../layouts/StudentLayout.svelte';
    import AsymmetricalLayout from '../../layouts/World/AsymmetricalLayout.svelte';
    import CarouselLayout from '../../layouts/World/CarouselLayout.svelte';
    import CinematicLayout from '../../layouts/World/CinematicLayout.svelte';
    import GridLayout from '../../layouts/World/GridLayout.svelte';

    export let world;
    $: worldData = world.data ?? world;
    $: themeData = worldData.theme;
    $: courses = worldData.courses ?? [];

    // Safely extract the layout preference, defaulting to 'grid'
    $: layoutPreference = themeData?.config?.ui?.course_layout || 'grid';

    const layoutRegistry = {
        grid: GridLayout,
        carousel: CarouselLayout,
        asymmetrical: AsymmetricalLayout,
        cinematic: CinematicLayout,
    };
</script>

<Layout theme={themeData}>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <header
            class="mb-12 border-b border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] pb-12"
        >
            <Link
                href="/worlds"
                class="text-xs font-mono uppercase tracking-widest text-[var(--text-color)] opacity-40 hover:opacity-100 hover:text-[var(--primary-color)] transition-colors mb-6 inline-block"
            >
                ← Databanks
            </Link>
            <h1
                class="text-4xl md:text-6xl font-black text-[var(--text-color)] drop-shadow-md mb-6 transition-colors duration-800"
            >
                {worldData.name}
            </h1>
            <p
                class="text-lg md:text-xl text-[var(--text-color)] opacity-70 max-w-3xl leading-relaxed border-l-2 border-[var(--primary-color)] pl-6 transition-colors duration-800"
            >
                {worldData.description}
            </p>
        </header>

        <div class="relative z-10">
            <h2
                class="text-sm font-mono text-[var(--text-color)] opacity-40 uppercase tracking-widest mb-8"
            >
                Available Curriculum
            </h2>

            {#if courses.length === 0}
                <div
                    class="p-12 bg-surface text-center shadow-inner rounded-xl"
                >
                    <p
                        class="text-[var(--text-color)] opacity-30 font-mono text-sm"
                    >
                        System empty. Awaiting Dungeon Master configurations...
                    </p>
                </div>
            {:else}
                <svelte:component
                    this={layoutRegistry[layoutPreference]}
                    {courses}
                />
            {/if}
        </div>
    </div>
</Layout>
