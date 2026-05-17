<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let worlds;
</script>

<Layout>
  <div class="fixed inset-0 z-0 pointer-events-none bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>

  <div class="relative z-10 pt-12 pb-24">
    <header class="text-center mb-20">
      <div class="inline-block px-4 py-1.5 mb-6 rounded-full border border-white/10 bg-white/5 text-xs font-mono text-white/50 uppercase tracking-widest shadow-inner">
        Global Matrix
      </div>
      <h1 class="text-5xl font-black text-white tracking-tight mb-6 drop-shadow-md">Select Realm</h1>
      <p class="text-white/40 font-mono text-sm uppercase tracking-widest">Connect to a learning matrix</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto px-4">
      {#each worlds.data as world}
        {@const cardColor = world.theme?.config?.palette?.primary || '#8b5cf6'}

        <Link href="/worlds/{world.slug}"
              class="group relative flex flex-col h-[280px] bg-[#18181b] rounded-2xl overflow-hidden border border-white/5 transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)]"
              style="--hover-color: {cardColor};">

          <div class="absolute top-0 left-0 right-0 h-1 bg-white/10 group-hover:bg-[var(--hover-color)] transition-colors duration-500"></div>

          <div class="absolute inset-0 pointer-events-none opacity-0 group-hover:opacity-20 transition-opacity duration-500"
               style="background: radial-gradient(circle at 50% 0%, var(--hover-color) 0%, transparent 60%);"></div>

          <div class="p-8 flex flex-col h-full relative z-10">

            <div class="flex justify-between items-start mb-6">
              <div class="w-12 h-12 rounded-xl bg-black/40 border border-white/10 flex items-center justify-center shadow-inner group-hover:border-[var(--hover-color)]/50 transition-colors">
                <span class="text-xl opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all drop-shadow-[0_0_8px_var(--hover-color)]">
                  {world.icon || '🌌'}
                </span>
              </div>
              <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-black/40 border border-white/5 text-[10px] font-mono uppercase tracking-wider text-green-400">
                <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div>
                Online
              </div>
            </div>

            <h2 class="text-2xl font-bold text-white mb-2 group-hover:text-[var(--hover-color)] transition-colors">
              {world.name}
            </h2>
            <p class="text-sm text-white/40 leading-relaxed line-clamp-3">
              {world.description}
            </p>

            <div class="mt-auto pt-6 flex items-center justify-between text-xs font-mono uppercase text-white/30">
               <span>Initialize</span>
               <span class="group-hover:text-[var(--hover-color)] transition-all duration-300 transform group-hover:translate-x-2 flex items-center gap-2">
                 Enter Matrix <span class="text-lg leading-none">→</span>
               </span>
            </div>
          </div>
        </Link>
      {/each}
    </div>
  </div>
</Layout>
