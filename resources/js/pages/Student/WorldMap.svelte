<script>
  import Layout from '../../layouts/StudentLayout.svelte';
  import { Link } from '@inertiajs/svelte';

  export let worlds; // Array from WorldResource
</script>

<Layout>
  <h1 class="text-4xl font-bold mb-8 text-center text-primary">Select Your Realm</h1>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    {#each worlds.data as world}
      <Link href="/worlds/{world.slug}"
            class="group relative overflow-hidden rounded-xl border-2 border-white/10 bg-gray-800/50 p-6 hover:border-blue-500 transition-all">

        <div class="relative z-10">
          <h2 class="text-2xl font-bold text-white">{world.name}</h2>
          <p class="text-gray-400 mt-2">{world.description}</p>
        </div>

        <div class="absolute bottom-0 right-0 p-4 opacity-20 group-hover:opacity-100 transition-opacity">
           <span style="color: {world.theme.primary_color}">✨</span>
        </div>
      </Link>
    {/each}
  </div>
</Layout>
