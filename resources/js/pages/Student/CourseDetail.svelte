<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let course;
  export let world;
  export let lessons = [];

  $: worldData = world.data ?? world;
  $: themeData = worldData.theme;

  $: mapLayout = themeData?.config?.ui?.map_layout || 'linear';
</script>

<Layout theme={themeData}>
  <header class="mb-16 border-b border-white/10 pb-8 text-center relative z-10">
    <Link href="/worlds/{worldData.slug}" class="absolute left-0 top-2 text-sm opacity-60 hover:opacity-100 transition-opacity z-20">
      ← Back to {worldData.name}
    </Link>
    <h1 class="text-5xl font-black text-primary drop-shadow-md tracking-wider uppercase">
      {course.name}
    </h1>
    <p class="text-xl mt-4 opacity-80 max-w-2xl mx-auto">Select your next quest</p>
  </header>

  <div class="map-viewport relative max-w-4xl mx-auto py-12">

    {#if mapLayout === 'linear'}
      <div class="layout-linear flex flex-col items-center gap-16 relative">
        <div class="absolute top-0 bottom-0 w-2 bg-primary/20 rounded-full z-0"></div>

        {#each lessons as lesson, i}
          <Link href="/lessons/{lesson.slug}" class="level-node bg-surface relative z-10 w-64 p-6 text-center group hover:scale-110 transition-transform cursor-pointer">
            <div class="text-3xl font-black text-primary opacity-50 group-hover:opacity-100 transition-opacity mb-2">
              {i + 1}
            </div>
            <h3 class="font-bold text-lg leading-tight group-hover:text-accent transition-colors">{lesson.title}</h3>
          </Link>
        {/each}
      </div>

    {:else if mapLayout === 'branching'}
      <div class="layout-branching flex flex-col items-center gap-12 relative">
        <div class="absolute top-0 bottom-0 w-2 bg-primary/20 rounded-full z-0"></div>

        {#each lessons as lesson, i}
          <Link href="/lessons/{lesson.slug}" class="level-node bg-surface relative z-10 w-64 p-4 text-center group hover:scale-105 transition-transform cursor-pointer {i % 2 === 0 ? 'mr-auto ml-[10%]' : 'ml-auto mr-[10%]'}">
            <div class="absolute top-1/2 w-[calc(50vw-22rem)] h-1 bg-primary/20 -z-10 {i % 2 === 0 ? 'left-full' : 'right-full'}"></div>

            <div class="flex items-center gap-4 {i % 2 === 0 ? 'flex-row' : 'flex-row-reverse'}">
              <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-black shadow-lg shadow-primary/30">
                {i + 1}
              </div>
              <h3 class="font-bold flex-1 text-left group-hover:text-accent transition-colors {i % 2 !== 0 ? 'text-right' : ''}">{lesson.title}</h3>
            </div>
          </Link>
        {/each}
      </div>

    {:else if mapLayout === 'hub_spoke'}
      <div class="layout-hub-spoke relative flex items-center justify-center min-h-[500px]">
        <div class="absolute w-32 h-32 rounded-full bg-primary flex items-center justify-center shadow-2xl shadow-primary/50 z-20 text-center p-4">
          <span class="font-black text-white leading-tight">Start<br>Here</span>
        </div>

        <div class="relative w-full h-full flex flex-wrap justify-center gap-8 p-12 z-10 mt-40">
          {#each lessons as lesson, i}
            <svg class="absolute inset-0 w-full h-full -z-10 pointer-events-none opacity-20 stroke-primary">
               <line x1="50%" y1="20%" x2="{20 + (i * 15)}%" y2="{50 + (i * 10)}%" stroke-width="2" stroke-dasharray="4"/>
            </svg>

            <Link href="/lessons/{lesson.slug}" class="level-node bg-surface w-48 p-5 text-center group hover:-translate-y-2 transition-transform cursor-pointer">
               <h3 class="font-bold text-sm group-hover:text-accent transition-colors">{lesson.title}</h3>
               <div class="mt-3 text-xs opacity-50">Quest {i + 1}</div>
            </Link>
          {/each}
        </div>
      </div>
    {/if}

  </div>
</Layout>

<style>
  /* Base Level Node logic - inherits from StudentLayout's .bg-surface */
  .level-node {
    /* These specific layout overrides ensure the card UI maps well to map nodes */
    text-decoration: none;
    display: block;
    border: var(--card-border, 1px solid rgba(255,255,255,0.1));
  }
</style>
