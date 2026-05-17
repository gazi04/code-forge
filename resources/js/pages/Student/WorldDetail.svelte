<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let world;
  $: worldData = world.data ?? world;
  $: themeData = worldData.theme;
  $: courses = worldData.courses ?? [];
</script>

<Layout theme={themeData}>
  <header class="mb-12 border-b border-white/5 pb-12 pt-8">
    <Link href="/worlds" class="text-xs font-mono uppercase tracking-widest text-white/40 hover:text-[var(--primary-color)] transition-colors mb-6 inline-block">
      ← Databanks
    </Link>
    <h1 class="text-4xl md:text-6xl font-black text-white drop-shadow-md mb-6">{worldData.name}</h1>
    <p class="text-lg md:text-xl text-white/60 max-w-3xl leading-relaxed border-l-2 border-[var(--primary-color)]/50 pl-6">
      {worldData.description}
    </p>
  </header>

  <div class="max-w-4xl">
    <h2 class="text-sm font-mono text-white/40 uppercase tracking-widest mb-6">Available Curriculum</h2>

    {#if courses.length === 0}
      <div class="p-12 rounded-xl bg-surface border border-white/5 text-center shadow-inner">
        <p class="text-white/30 font-mono text-sm">System empty. Awaiting Dungeon Master configurations...</p>
      </div>
    {:else}
      <div class="flex flex-col gap-3">
        {#each courses as course, i}
          <Link href="/course/{course.slug}" class="group relative flex flex-col md:flex-row items-start md:items-center justify-between p-6 rounded-lg bg-surface border border-transparent hover:border-[var(--primary-color)]/50 transition-all duration-300 overflow-hidden">

            <div class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--primary-color)] opacity-0 group-hover:opacity-100 transition-opacity"></div>

            <div class="flex items-center gap-6 relative z-10">
              <div class="text-2xl font-black text-white/10 group-hover:text-[var(--primary-color)]/30 transition-colors w-8 text-center">
                {String(i + 1).padStart(2, '0')}
              </div>
              <h3 class="text-xl font-bold text-white/80 group-hover:text-white group-hover:translate-x-2 transition-all duration-300">
                {course.name}
              </h3>
            </div>

            <div class="mt-4 md:mt-0 flex items-center gap-4 opacity-0 group-hover:opacity-100 transform translate-x-4 group-hover:translate-x-0 transition-all duration-300">
               <span class="text-sm font-mono text-[var(--primary-color)]">Initialize Sequence →</span>
            </div>
          </Link>
        {/each}
      </div>
    {/if}
  </div>
</Layout>
