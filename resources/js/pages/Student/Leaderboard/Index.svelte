<script>
    import { router } from '@inertiajs/svelte';
    import PlayerAnchor from '../../../components/PlayerAnchor.svelte';
    import Podium from '../../../components/Podium.svelte';
    import Roster from '../../../components/Roster.svelte';
    import Layout from '../../../layouts/StudentLayout.svelte';

    let { leaders = [], scope = 'weekly', player } = $props();

    function switchScope(newScope) {
        router.visit(`/leaderboard?scope=${newScope}`, {
            preserveState: true,
            preserveScroll: true,
        });
    }
</script>

<Layout>
    <div class="max-w-2xl mx-auto pb-28">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-black tracking-wide text-[var(--text-color)] mb-1">
                🏆 Leaderboard
            </h1>
            <p class="text-sm text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono uppercase tracking-widest">
                Top 50 cadets
            </p>
        </div>

        <div class="flex gap-2 justify-center mb-10">
            <button
                onclick={() => switchScope('weekly')}
                class="px-5 py-2 rounded-xl text-sm font-black uppercase tracking-widest transition-all
                    {scope === 'weekly'
                        ? 'bg-[var(--primary-color)] text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]'
                        : 'bg-[var(--surface-color)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'}"
            >
                Weekly Sprint
            </button>
            <button
                onclick={() => switchScope('all_time')}
                class="px-5 py-2 rounded-xl text-sm font-black uppercase tracking-widest transition-all
                    {scope === 'all_time'
                        ? 'bg-[var(--primary-color)] text-[var(--bg-color)] shadow-[0_4px_20px_color-mix(in_srgb,var(--primary-color)_30%,transparent)]'
                        : 'bg-[var(--surface-color)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'}"
            >
                All Time
            </button>
        </div>

        {#if leaders.length === 0}
            <div class="text-center py-20 text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] font-mono">
                <p class="text-4xl mb-4">📡</p>
                <p class="text-sm uppercase tracking-widest">No rankings yet. Be the first.</p>
            </div>
        {:else}
            <Podium {leaders} playerName={player?.name ?? ''} />
            <Roster {leaders} playerName={player?.name ?? ''} />
        {/if}
    </div>

    <PlayerAnchor {player} />
</Layout>
