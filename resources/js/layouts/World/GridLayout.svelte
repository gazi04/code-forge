<script>
  import { Link } from '@inertiajs/svelte';
  export let courses = [];
</script>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-24">
  {#each courses as course, i}
    <Link href="/course/{course.slug}" class="group relative flex flex-col p-8 bg-surface border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:-translate-y-1 transition-all duration-300 overflow-hidden rounded-2xl shadow-lg">
      <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-30 group-hover:scale-110 transition-all duration-500 font-black text-6xl text-[var(--text-color)]">
        {String(i + 1).padStart(2, '0')}
      </div>

      <div class="relative z-10 flex-1">
        <h3 class="text-xl font-bold text-[var(--text-color)] mb-3 group-hover:text-[var(--primary-color)] transition-colors duration-300">
          {course.name}
        </h3>
        <p class="text-sm text-[var(--text-color)] opacity-60 line-clamp-3">
          {course.description || 'Initialize the sequence to discover what lies within this sector.'}
        </p>
      </div>

      <div class="mt-8 pt-4 border-t border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] flex items-center justify-between opacity-60 group-hover:opacity-100 transition-opacity">
        <span class="text-xs font-mono uppercase tracking-wider text-[var(--primary-color)]">Initialize →</span>
      </div>
    </Link>
  {/each}
</div>
