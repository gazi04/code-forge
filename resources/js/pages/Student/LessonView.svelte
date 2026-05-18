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
  <header class="sticky top-14 z-40 -mx-4 px-4 py-4 mb-8 backdrop-blur-md bg-[color-mix(in_srgb,var(--bg-color)_90%,transparent)] border-b border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-colors duration-800">
    <div class="flex items-center gap-4">
      <div class="w-10 h-10 rounded-md bg-surface flex items-center justify-center border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-inner">
        {#if actualLesson.is_boss}
          <span class="text-xl drop-shadow-[0_0_8px_rgba(239,68,68,0.8)]">🔥</span>
        {:else}
          <span class="text-xl">🛡️</span>
        {/if}
      </div>
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

  <div class="space-y-8 max-w-4xl mx-auto pb-24 relative z-10">
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
</Layout>
