<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let worlds;
</script>

<Layout>
  <header class="text-center mb-16 pt-8">
    <h1 class="text-4xl font-black text-white tracking-tight mb-4">Select Realm</h1>
    <p class="text-white/40 font-mono text-sm uppercase tracking-widest">Connect to a learning matrix</p>
  </header>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
    {#each worlds.data as world}
      {@const cardColor = world.theme?.config?.palette?.primary || '#8b5cf6'}

      <Link href="/worlds/{world.slug}"
            class="group relative flex flex-col h-full bg-[#111827] rounded-xl overflow-hidden border border-white/10 transition-all duration-300 hover:-translate-y-1 shadow-lg"
            style="--hover-color: {cardColor};">

        <div class="h-1 w-full bg-white/10 group-hover:bg-[var(--hover-color)] transition-colors"></div>

        <div class="p-8 flex flex-col flex-grow relative z-10">
          <h2 class="text-2xl font-bold text-white mb-3 group-hover:text-[var(--hover-color)] transition-colors">
            {world.name}
          </h2>
          <p class="text-sm text-white/50 leading-relaxed flex-grow">
            {world.description}
          </p>

          <div class="mt-8 flex items-center justify-between text-xs font-mono text-white/30 uppercase">
             <span>Status: Online</span>
             <span class="group-hover:text-[var(--hover-color)] transition-colors transition-transform group-hover:translate-x-1">Enter →</span>
          </div>
        </div>

        <div class="absolute inset-0 pointer-events-none opacity-0 group-hover:opacity-10 transition-opacity" style="background: radial-gradient(circle at 50% 100%, var(--hover-color) 0%, transparent 70%);"></div>
      </Link>
    {/each}
  </div>
</Layout>
