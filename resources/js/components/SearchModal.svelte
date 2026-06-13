<script>
    import { useHttp, router } from '@inertiajs/svelte';

    let { onclose } = $props();

    let query = $state('');
    let results = $state({ worlds: [], courses: [], lessons: [] });
    let debounceTimer = null;
    let inputEl;

    const http = useHttp({});

    let totalResults = $derived(
        results.worlds.length + results.courses.length + results.lessons.length,
    );

    let hasQuery = $derived(query.trim().length >= 2);

    $effect(() => {
        inputEl?.focus();
    });

    function handleInput() {
        clearTimeout(debounceTimer);
        if (query.trim().length < 2) {
            results = { worlds: [], courses: [], lessons: [] };
            return;
        }
        debounceTimer = setTimeout(() => {
            http.get(`/search?q=${encodeURIComponent(query.trim())}`, {
                onSuccess: (data) => {
                    results = data;
                },
            });
        }, 300);
    }

    function navigate(url) {
        router.visit(url);
        onclose();
    }

    function handleModalKeydown(e) {
        if (e.key === 'Escape') {
            onclose();
        }
    }

    const DIFFICULTY_LABELS = ['', 'Beginner', 'Easy', 'Medium', 'Hard', 'Expert'];
    const TIER_ICONS = { explorer: '🔭', builder: '🔨', coder: '💻' };
</script>

<svelte:window onkeydown={handleModalKeydown} />

<!-- Backdrop -->
<div
    class="fixed inset-0 z-[9999] flex items-start justify-center pt-[8vh] sm:pt-[15vh] px-4"
    role="dialog"
    aria-modal="true"
>
    <!-- Dim overlay -->
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        role="presentation"
        onclick={onclose}
    ></div>

    <!-- Modal panel -->
    <div
        class="relative w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl"
        style="background: var(--surface-color); border: 2px solid color-mix(in srgb, var(--primary-color) 30%, transparent); box-shadow: 0 0 80px color-mix(in srgb, var(--primary-color) 20%, transparent);"
    >
        <!-- Search input row -->
        <div
            class="flex items-center gap-3 sm:gap-4 px-4 py-4 sm:px-6 sm:py-5 border-b"
            style="border-color: color-mix(in srgb, var(--text-color) 8%, transparent);"
        >
            <span class="text-2xl shrink-0 text-[color-mix(in_srgb,var(--text-color)_35%,transparent)]">🔍</span>
            <input
                bind:this={inputEl}
                bind:value={query}
                oninput={handleInput}
                type="text"
                placeholder="Search worlds, courses, lessons..."
                class="flex-1 min-w-0 bg-transparent text-base sm:text-xl font-bold font-mono text-[var(--text-color)] placeholder-[color-mix(in_srgb,var(--text-color)_25%,transparent)] outline-none"
            />
            {#if http.processing}
                <span
                    class="text-xs font-mono uppercase tracking-widest text-[color-mix(in_srgb,var(--primary-color)_60%,transparent)] animate-pulse shrink-0"
                >Searching…</span>
            {/if}
            <kbd
                class="shrink-0 hidden sm:inline text-xs px-2 py-1 rounded-lg border font-mono font-bold text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] border-[color-mix(in_srgb,var(--text-color)_12%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_4%,transparent)]"
            >Esc</kbd>
        </div>

        <!-- Results -->
        <div class="max-h-[60vh] overflow-y-auto">
            {#if !hasQuery}
                <div class="px-4 py-10 text-center">
                    <p class="text-sm font-mono font-bold text-[color-mix(in_srgb,var(--text-color)_25%,transparent)] uppercase tracking-widest">
                        Type at least 2 characters to search
                    </p>
                </div>
            {:else if hasQuery && !http.processing && totalResults === 0}
                <div class="px-4 py-10 text-center">
                    <p class="text-lg font-black text-[color-mix(in_srgb,var(--text-color)_35%,transparent)]">
                        No results for "{query.trim()}"
                    </p>
                </div>
            {:else}
                <!-- Worlds -->
                {#if results.worlds.length > 0}
                    <div class="pt-3 pb-1 px-4">
                        <p class="text-xs font-mono font-bold uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]">
                            Worlds
                        </p>
                    </div>
                    {#each results.worlds as world (world.slug)}
                        <button
                            type="button"
                            onclick={() => navigate(`/worlds/${world.slug}`)}
                            class="w-full flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-4 text-left transition-colors duration-150 hover:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] active:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)]"
                        >
                            <span
                                class="shrink-0 w-4 h-4 rounded-full"
                                style="background: {world.primary_color}; box-shadow: 0 0 6px {world.primary_color}55;"
                            ></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-lg font-black text-[var(--text-color)] truncate">{world.name}</p>
                                {#if world.description}
                                    <p class="text-sm font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] truncate mt-1">{world.description}</p>
                                {/if}
                            </div>
                            <span class="shrink-0 text-2xl text-[color-mix(in_srgb,var(--text-color)_20%,transparent)]">→</span>
                        </button>
                    {/each}
                {/if}

                <!-- Courses -->
                {#if results.courses.length > 0}
                    <div class="pt-3 pb-1 px-4 {results.worlds.length > 0 ? 'border-t border-[color-mix(in_srgb,var(--text-color)_6%,transparent)]' : ''}">
                        <p class="text-xs font-mono font-bold uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]">
                            Courses
                        </p>
                    </div>
                    {#each results.courses as course (course.slug)}
                        <button
                            type="button"
                            onclick={() => navigate(`/course/${course.slug}`)}
                            class="w-full flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-4 text-left transition-colors duration-150 hover:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] active:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)]"
                        >
                            <span class="shrink-0 text-2xl leading-none">{TIER_ICONS[course.age_tier] ?? '🎓'}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-lg font-black text-[var(--text-color)] truncate">{course.name}</p>
                                <p class="text-sm font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] truncate mt-1">
                                    {course.world_name} · {DIFFICULTY_LABELS[course.difficulty] ?? 'Unknown'}
                                </p>
                            </div>
                            <span class="shrink-0 text-2xl text-[color-mix(in_srgb,var(--text-color)_20%,transparent)]">→</span>
                        </button>
                    {/each}
                {/if}

                <!-- Lessons -->
                {#if results.lessons.length > 0}
                    <div class="pt-3 pb-1 px-4 {results.worlds.length > 0 || results.courses.length > 0 ? 'border-t border-[color-mix(in_srgb,var(--text-color)_6%,transparent)]' : ''}">
                        <p class="text-xs font-mono font-bold uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)]">
                            Lessons
                        </p>
                    </div>
                    {#each results.lessons as lesson (lesson.slug)}
                        <button
                            type="button"
                            onclick={() => navigate(`/lessons/${lesson.slug}`)}
                            class="w-full flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-4 text-left transition-colors duration-150 hover:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] active:bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)]"
                        >
                            <span class="shrink-0 text-2xl leading-none">{lesson.is_boss ? '👑' : '⚡'}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-lg font-black text-[var(--text-color)] truncate">{lesson.name}</p>
                                <p class="text-sm font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)] truncate mt-1">
                                    {lesson.course_name} · {lesson.world_name} · ✨{lesson.xp_reward}xp
                                </p>
                            </div>
                            <span class="shrink-0 text-2xl text-[color-mix(in_srgb,var(--text-color)_20%,transparent)]">→</span>
                        </button>
                    {/each}
                {/if}

                <!-- Bottom padding -->
                <div class="h-2"></div>
            {/if}
        </div>
    </div>
</div>
