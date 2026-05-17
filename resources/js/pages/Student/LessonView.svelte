<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import TextBlock from '../../components/Blocks/TextBlock.svelte';

  export let lesson;
  export let theme;

  $: actualLesson = lesson?.data ?? lesson;
  const blockRegistry = { text_content: TextBlock };
  $: blocks = actualLesson?.blocks || [];
</script>

<Layout {theme}>
  <header class="sticky top-14 z-40 -mx-4 px-4 py-4 mb-8 backdrop-blur-md bg-[var(--bg-color)]/90 border-b border-white/5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div class="flex items-center gap-4">
      <div class="w-10 h-10 rounded-md bg-surface flex items-center justify-center border border-white/10 shadow-inner">
        {#if actualLesson.is_boss}
          <span class="text-xl drop-shadow-[0_0_8px_rgba(239,68,68,0.8)]">🔥</span>
        {:else}
          <span class="text-xl">🛡️</span>
        {/if}
      </div>
      <div>
        <h1 class="text-xl font-bold tracking-wide">{actualLesson.name}</h1>
        <p class="text-xs text-white/50 uppercase tracking-wider">Estimated: {actualLesson.estimated_duration}m</p>
      </div>
    </div>

    <div class="flex gap-3 text-sm font-medium">
      <div class="bg-[color:var(--primary-color)]/10 px-4 py-1.5 rounded-md border border-[color:var(--primary-color)]/30 text-[var(--primary-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_20%,transparent)]">
        ✨ {actualLesson.xp_reward} XP
      </div>
      <div class="bg-[color:var(--accent-color)]/10 px-4 py-1.5 rounded-md border border-[color:var(--accent-color)]/30 text-[var(--accent-color)] shadow-[0_0_10px_color-mix(in_srgb,var(--accent-color)_20%,transparent)]">
        💰 {actualLesson.coin_reward}
      </div>
    </div>
  </header>

  <div class="space-y-8 max-w-4xl mx-auto pb-24 relative z-10">
    {#if blocks.length === 0}
      <div class="bg-surface rounded-xl p-12 text-center border border-white/5 shadow-2xl">
        <div class="inline-block p-4 rounded-full bg-white/5 mb-4">
          <span class="text-3xl opacity-50">📜</span>
        </div>
        <p class="text-white/40 font-mono text-sm">Error 404: Directives not found in the archives.</p>
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
</Layout>
