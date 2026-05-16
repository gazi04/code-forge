<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let world;

  $: worldData = world.data ?? world;
  $: themeData = worldData.theme;
  $: courses = worldData.courses ?? [];
</script>

<Layout theme={themeData}>
  <header class="mb-12 border-b border-white/10 pb-8">
    <Link href="/worlds" class="text-sm opacity-60 hover:opacity-100 mb-4 inline-block transition-opacity">
      ← Return to Map
    </Link>
    <h1 class="text-5xl font-black text-primary drop-shadow-sm">{worldData.name}</h1>
    <p class="text-xl mt-4 max-w-3xl opacity-80 leading-relaxed">
      {worldData.description}
    </p>
  </header>

  <div class="grid gap-4 max-w-3xl">
    <h2 class="text-2xl font-bold tracking-tight">Quests Available</h2>

    {#if courses.length === 0}
      <div class="p-10 rounded-2xl bg-surface border border-dashed border-white/20 text-center">
        <p class="opacity-60 italic">No courses have been discovered in this realm yet.</p>
      </div>
    {:else}
      {#each courses as course}
        <div class="group flex items-center justify-between p-6 rounded-2xl bg-surface border border-white/10 hover:border-primary/50 transition-all">
          <h3 class="text-xl font-semibold group-hover:text-accent transition-colors">{course.name}</h3>

          <Link href="/course/{course.slug}"
                class="px-6 py-2 rounded-xl bg-primary text-white font-bold shadow-lg shadow-primary/20 hover:scale-105 transition-transform">
            Enter
          </Link>
        </div>
      {/each}
    {/if}
  </div>
</Layout>
