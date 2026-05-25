<script>
  import { Link } from '@inertiajs/svelte';
  import LabyrinthBlock from '../../components/Blocks/LabyrinthBlock.svelte';
  import CodeChallengeBlock from '../../components/Blocks/CodeChallengeBlock.svelte';
  import QuizBlock from '../../components/Blocks/QuizBlock.svelte';
  import TextBlock from '../../components/Blocks/TextBlock.svelte';
  import SequenceBlock from '../../components/Blocks/SequenceBlock.svelte';
  import BugHuntBlock from '../../components/Blocks/BugHuntBlock.svelte';
  import VariableMatchingBlock from '../../components/Blocks/VariableMatchingBlock.svelte';
  import Layout from '../../layouts/StudentLayout.svelte';

  export let lesson;
  export let theme;
  export let course_slug;
  export let previous_lesson_slug = null;
  export let next_lesson_slug = null;

  $: actualLesson = lesson?.data ?? lesson;
  const blockRegistry = {
      text_content: TextBlock,
      code_challenge: CodeChallengeBlock,
      quiz: QuizBlock,
      labyrinth_challenge: LabyrinthBlock,
      sequence_challenge: SequenceBlock,
      bughunt_challenge: BugHuntBlock,
      variable_matching_challenge: VariableMatchingBlock
  };
  $: blocks = actualLesson?.blocks || [];
</script>

<Layout {theme}>
  <header class="sticky top-14 z-40 -mx-4 px-4 py-4 mb-8 backdrop-blur-md bg-[color-mix(in_srgb,var(--bg-color)_90%,transparent)] border-b border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-colors duration-800">
    <div class="flex items-center gap-4">
      <Link href="/course/{course_slug}" class="w-10 h-10 rounded-md bg-surface flex items-center justify-center border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-inner hover:border-[var(--primary-color)] transition-colors group">
        <span class="text-xl group-hover:scale-110 transition-transform">↩️</span>
      </Link>
      <div>
        <h1 class="text-xl font-bold tracking-wide text-[var(--text-color)]">{actualLesson.name}</h1>
        <p class="text-xs text-[var(--text-color)] opacity-50 uppercase tracking-wider">Estimated: {actualLesson.estimated_duration}m</p>
      </div>
    </div>

    <div class="flex gap-3 text-sm font-medium">
      <div class="bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] px-4 py-1.5 rounded-md border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] text-[var(--primary-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-800">
        ✨ {actualLesson.xp_reward} XP
      </div>
      <div class="bg-[color-mix(in_srgb,var(--accent-color)_10%,transparent)] px-4 py-1.5 rounded-md border border-[color-mix(in_srgb,var(--accent-color)_30%,transparent)] text-[var(--accent-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--accent-color)_20%,transparent)] transition-colors duration-800">
        💰 {actualLesson.coin_reward}
      </div>
    </div>
  </header>

  <div class="space-y-8 max-w-4xl mx-auto pb-12 relative z-10">
    {#if blocks.length === 0}
      <div class="bg-surface p-12 text-center shadow-2xl">
        <div class="inline-block p-4 rounded-full bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] mb-4">
          <span class="text-3xl opacity-50">📜</span>
        </div>
        <p class="text-[var(--text-color)] opacity-40 font-mono text-sm">Error 404: Directives not found in the archives.</p>
      </div>
    {:else}
      {#each blocks as block, index}
        <div class="block-wrapper">
          {#if blockRegistry[block.type]}
            <svelte:component this={blockRegistry[block.type]} data={block.data} {index} />
          {:else}
            <div class="p-4 bg-red-950/30 border border-red-500/50 text-red-400 rounded-md font-mono text-sm shadow-[0_0_15px_rgba(239,68,68,0.1)]">
              > _Unknown block execution halted: <strong>{block.type}</strong>
            </div>
          {/if}
        </div>
      {/each}
    {/if}
  </div>

  <footer class="max-w-4xl mx-auto pt-8 pb-24 border-t border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex justify-between items-center gap-4 relative z-10">
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
        <Link
          href="/lessons/{next_lesson_slug}"
          class="px-6 py-3 rounded-xl bg-[var(--primary-color)] text-sm font-black uppercase tracking-widest text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)] hover:scale-[1.03] active:scale-[0.98] transition-all flex items-center gap-2"
        >
          Advance <span>→</span>
        </Link>
      {:else}
        <Link
          href="/course/{course_slug}"
          class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 text-sm font-black uppercase tracking-widest text-zinc-950 shadow-[0_4px_25px_rgba(245,158,11,0.4)] hover:scale-[1.03] active:scale-[0.98] transition-all flex items-center gap-2 border border-amber-300 animate-pulse"
        >
          🏆 Finish Quest
        </Link>
      {/if}
    </div>
  </footer>
</Layout>
