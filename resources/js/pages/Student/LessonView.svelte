<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import TextBlock from '../../components/Blocks/TextBlock.svelte';

  export let lesson;
  export let theme;

  $: actualLesson = lesson?.data ?? lesson;

  const blockRegistry = {
    text_content: TextBlock
  };

  $: blocks = actualLesson?.blocks || [];
</script>

<Layout {theme}>
  <header class="flex justify-between items-center mb-8 relative z-10">
    <div class="flex items-center gap-4">
      <div class="p-3 rounded-lg bg-white/10">
        {#if actualLesson.is_boss}
          <span class="text-2xl">🔥</span>
        {:else}
          <span class="text-2xl">🛡️</span>
        {/if}
      </div>
      <div>
        <h1 class="text-2xl font-bold">{actualLesson.name}</h1>
        <p class="text-sm text-gray-400">Estimated: {actualLesson.estimated_duration} mins</p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="bg-blue-900/30 px-4 py-2 rounded-full border border-blue-500/50 text-primary">
        ✨ {actualLesson.xp_reward} XP
      </div>
      <div class="bg-yellow-900/30 px-4 py-2 rounded-full border border-yellow-500/50 text-accent">
        💰 {actualLesson.coin_reward}
      </div>
    </div>
  </header>

  <div class="space-y-8 max-w-4xl mx-auto pb-24 relative z-10">
    {#if blocks.length === 0}
      <div class="bg-surface rounded-2xl p-8 text-center border">
        <p class="text-gray-400 italic">This quest contains no directives yet.</p>
      </div>
    {:else}
      {#each blocks as block, index}
        <div class="block-wrapper">
          {#if blockRegistry[block.type]}
            <svelte:component
              this={blockRegistry[block.type]}
              data={block.data}
              {index}
            />
          {:else}
            <div class="p-4 bg-red-900/20 border border-red-500 text-red-200 rounded-xl">
              Unknown magical encounter type: <strong>{block.type}</strong>
            </div>
          {/if}
        </div>
      {/each}
    {/if}
  </div>
</Layout>
