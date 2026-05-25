<script>
  import { Link } from '@inertiajs/svelte';
  export let courses = [];
</script>

<style>
  .hide-scrollbar::-webkit-scrollbar { display: none; }
  .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<div class="flex overflow-x-auto snap-x snap-mandatory gap-6 pb-12 hide-scrollbar w-full">
  {#each courses as course, i}
    <Link href="/course/{course.slug}" class="snap-center shrink-0 w-[85vw] md:w-[400px] group flex flex-col p-8 bg-surface border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] transition-all duration-300 rounded-2xl shadow-xl">
      <span class="text-sm font-mono text-[var(--primary-color)] mb-4 block">Sector {String(i + 1).padStart(2, '0')}</span>
      <h3 class="text-2xl font-black text-[var(--text-color)] mb-4 group-hover:translate-x-2 transition-transform duration-300">
        {course.name}
      </h3>
      <div class="mt-auto pt-8 flex items-center gap-2 text-[var(--text-color)] opacity-50 group-hover:opacity-100 group-hover:text-[var(--primary-color)] transition-all">
        <span class="text-sm font-bold uppercase tracking-widest">Enter Module</span>
      </div>
    </Link>
  {/each}
</div>
