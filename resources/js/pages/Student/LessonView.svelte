<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  export let lesson; // From LessonResource
  export let theme;  // Passed separately from Controller
</script>

<Layout {theme}>
  <header class="flex justify-between items-center mb-8">
    <div class="flex items-center gap-4">
      <div class="p-3 rounded-lg bg-white/10">
        {#if lesson.is_boss}
          <span class="text-2xl">🔥</span>
        {:else}
          <span class="text-2xl">🛡️</span>
        {/if}
      </div>
      <div>
        <h1 class="text-2xl font-bold">{lesson.name}</h1>
        <p class="text-sm text-gray-400">Estimated: {lesson.estimated_duration} mins</p>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="bg-blue-900/30 px-4 py-2 rounded-full border border-blue-500/50">
        ✨ {lesson.xp_reward} XP
      </div>
      <div class="bg-yellow-900/30 px-4 py-2 rounded-full border border-yellow-500/50">
        💰 {lesson.coin_reward}
      </div>
    </div>
  </header>

  <div class="space-y-8 max-w-4xl mx-auto">
      <div class="bg-white/5 rounded-2xl p-8 border border-white/10">
          <p class="text-center text-gray-500 italic">
            Quest content loading from the scrolls...
          </p>
      </div>
  </div>
</Layout>
