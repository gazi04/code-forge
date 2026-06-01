<script>
    import { Link } from '@inertiajs/svelte';
    export let courses = [];
</script>

<div
    class="h-[75vh] w-full overflow-y-auto snap-y snap-mandatory hide-scrollbar rounded-3xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl relative"
>
    <div
        class="absolute top-6 right-6 z-20 animate-pulse text-[10px] uppercase tracking-widest font-mono text-[var(--text-color)] opacity-50"
    >
        ↓ Scroll to navigate
    </div>

    {#each courses as course, i}
        <div
            class="h-full w-full snap-center shrink-0 flex items-center justify-center p-8 md:p-16 relative overflow-hidden group"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] to-[color-mix(in_srgb,var(--bg-color)_90%,transparent)] opacity-50 group-hover:opacity-100 transition-opacity duration-700"
            ></div>

            <Link
                href="/course/{course.slug}"
                class="relative z-10 w-full max-w-3xl text-center flex flex-col items-center"
            >
                <span
                    class="text-6xl md:text-8xl font-black text-[var(--text-color)] opacity-5 mb-4 group-hover:scale-110 transition-transform duration-700"
                    >{String(i + 1).padStart(2, '0')}</span
                >
                <h3
                    class="text-4xl md:text-5xl font-black text-[var(--text-color)] mb-6 drop-shadow-lg group-hover:text-[var(--primary-color)] transition-colors duration-500"
                >
                    {course.name}
                </h3>
                <div
                    class="px-8 py-3 rounded-full border border-[var(--primary-color)] text-[var(--primary-color)] text-sm font-bold uppercase tracking-widest hover:bg-[var(--primary-color)] hover:text-[var(--bg-color)] transition-colors duration-300 cursor-pointer shadow-[0_0_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]"
                >
                    Begin Sequence
                </div>
            </Link>
        </div>
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
