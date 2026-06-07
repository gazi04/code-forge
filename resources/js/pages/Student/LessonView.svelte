<script>
    import { Link, page, useForm, router } from '@inertiajs/svelte';
    import BugHuntBlock from '../../components/Blocks/BugHuntBlock.svelte';
    import CodeChallengeBlock from '../../components/Blocks/CodeChallengeBlock.svelte';
    import LabyrinthBlock from '../../components/Blocks/LabyrinthBlock.svelte';
    import QuizBlock from '../../components/Blocks/QuizBlock.svelte';
    import SequenceBlock from '../../components/Blocks/SequenceBlock.svelte';
    import TextBlock from '../../components/Blocks/TextBlock.svelte';
    import VariableMatchingBlock from '../../components/Blocks/VariableMatchingBlock.svelte';
    import Layout from '../../layouts/StudentLayout.svelte';
    import LessonHeader from '../../components/LessonHeader.svelte';

    let {
        lesson,
        theme,
        course_slug,
        previous_lesson_slug = null,
        next_lesson_slug = null,
        cleared_block_indices = [],
        is_completed = false,
    } = $props();

    let actualLesson = $derived(lesson?.data ?? lesson);
    let blocks = $derived(actualLesson?.blocks || []);

    const blockRegistry = {
        text_content: TextBlock,
        code_challenge: CodeChallengeBlock,
        quiz: QuizBlock,
        labyrinth_challenge: LabyrinthBlock,
        sequence_challenge: SequenceBlock,
        bughunt_challenge: BugHuntBlock,
        variable_matching_challenge: VariableMatchingBlock,
    };

    const claimForm = useForm({});

    let errorMessage = $state('');

    function handleAdvanceOrFinish() {
        errorMessage = '';

        claimForm.post(`/lessons/${actualLesson.slug}/submit`, {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.error) {
                    errorMessage = errors.error;
                    setTimeout(() => {
                        errorMessage = '';
                    }, 5000);
                }
            },
            onSuccess: (page) => {
                // Define the routing behavior
                const navigateForward = () => {
                    if (next_lesson_slug) {
                        router.visit(`/lessons/${next_lesson_slug}`);
                    } else {
                        router.visit(`/course/${course_slug}`);
                    }
                };

                // Check if the backend flagged a level up
                if (page.props.flash?.game_result?.leveled_up) {
                    // Pause navigation and wait for the modal to close
                    window.addEventListener(
                        'levelUpClosed',
                        function handler() {
                            window.removeEventListener(
                                'levelUpClosed',
                                handler,
                            );
                            navigateForward();
                        },
                    );
                } else {
                    // No level up occurred, seamlessly move to the next sector instantly
                    navigateForward();
                }
            },
        });
    }
</script>

<Layout {theme}>
    <LessonHeader lesson={actualLesson} {course_slug} {is_completed} />

    <div class="space-y-8 max-w-4xl mx-auto pb-4 relative z-10">
        {#if blocks.length === 0}
            <div class="bg-surface p-12 text-center shadow-2xl">
                <div
                    class="inline-block p-4 rounded-full bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] mb-4"
                >
                    <span class="text-3xl opacity-50">📜</span>
                </div>
                <p
                    class="text-[var(--text-color)] opacity-40 font-mono text-sm"
                >
                    Error 404: Directives not found in the archives.
                </p>
            </div>
        {:else}
            {#each blocks as block, index}
                <div class="block-wrapper">
                    {#if cleared_block_indices.includes(index)}
                        <div
                            class="absolute -top-3 right-6 z-30 pointer-events-none bg-emerald-500/10 backdrop-blur border border-emerald-500/40 text-emerald-400 text-[10px] font-mono tracking-widest uppercase px-2.5 py-1 rounded-md shadow-[0_0_15px_rgba(16,185,129,0.15)]"
                        >
                            ✓ Verified Clear
                        </div>
                    {/if}

                    {#if blockRegistry[block.type]}
                        <svelte:component
                            this={blockRegistry[block.type]}
                            data={block.data}
                            {index}
                            lessonSlug={actualLesson.slug}
                            isAlreadyCleared={cleared_block_indices.includes(
                                index,
                            )}
                        />
                    {:else}
                        <div
                            class="p-4 bg-red-950/30 border border-red-500/50 text-red-400 rounded-md font-mono text-sm shadow-[0_0_15px_rgba(239,68,68,0.1)]"
                        >
                            > _Unknown block execution halted: <strong
                                >{block.type}</strong
                            >
                        </div>
                    {/if}
                </div>
            {/each}
        {/if}
    </div>

    <footer
        class="max-w-4xl mx-auto mt-16 pt-8 pb-24 border-t border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex justify-between items-center gap-4 relative z-10"
    >
        <div>
            {#if previous_lesson_slug}
                <Link
                    href="/lessons/{previous_lesson_slug}"
                    class="px-5 py-3 rounded-xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] bg-surface text-sm font-bold uppercase tracking-wider text-[var(--text-color)] opacity-70 hover:opacity-100 hover:border-[var(--text-color)] transition-all flex items-center gap-2"
                >
                    <span>←</span> Retreat
                </Link>
            {/if}
        </div>

        <div>
            {#if next_lesson_slug}
                <button
                    onclick={handleAdvanceOrFinish}
                    disabled={claimForm.processing}
                    class="px-6 py-3 rounded-xl bg-[var(--primary-color)] text-sm font-black uppercase tracking-widest text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)] hover:scale-[1.03] active:scale-[0.98] transition-all flex items-center gap-2 disabled:opacity-60"
                >
                    {claimForm.processing ? 'Saving Progress...' : 'Advance'}
                    <span>→</span>
                </button>
            {:else}
                <button
                    onclick={handleAdvanceOrFinish}
                    disabled={claimForm.processing}
                    class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 text-sm font-black uppercase tracking-widest text-zinc-950 shadow-[0_4px_25px_rgba(245,158,11,0.4)] hover:scale-[1.03] active:scale-[0.98] transition-all flex items-center gap-2 border border-amber-300 disabled:opacity-60"
                >
                    🏆 {claimForm.processing
                        ? 'Securing Spoils...'
                        : 'Finish Quest'}
                </button>
            {/if}
        </div>
    </footer>

    {#if errorMessage}
        <div
            class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-md bg-rose-950/90 backdrop-blur-xl border border-rose-500/50 text-rose-300 px-6 py-4 rounded-2xl shadow-[0_0_30px_rgba(225,29,72,0.2)] flex items-start gap-4 animate-fade-in-up"
        >
            <div class="text-2xl mt-0.5 animate-pulse">⚠️</div>
            <div class="flex-1 flex flex-col gap-1">
                <span
                    class="text-[10px] uppercase tracking-widest font-black text-rose-500"
                    >Access Denied</span
                >
                <span class="font-mono text-sm font-medium leading-relaxed"
                    >{errorMessage}</span
                >
            </div>
            <button
                onclick={() => (errorMessage = '')}
                class="opacity-50 hover:opacity-100 hover:text-white transition-colors text-lg"
                aria-label="Close notification"
            >
                ✕
            </button>
        </div>
    {/if}
</Layout>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)
            forwards;
    }
</style>
