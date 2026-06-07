<script>
    import Layout from '../../../layouts/StudentLayout.svelte';
    import AchievementsGrid from '@/components/Profile/AchievementsGrid.svelte';
    import HeroBanner from '@/components/Profile/HeroBanner.svelte';
    import QuestLedger from '@/components/Profile/QuestLedger.svelte';
    import SystemSettings from '@/components/Profile/SystemSettings.svelte';

    let { hero, ledger, achievements, preferences } = $props();
</script>

<Layout>
    <div class="max-w-2xl mx-auto">
        <header class="mb-6">
            <div
                class="inline-block px-4 py-1.5 mb-3 rounded-full border border-white/10 bg-white/5 text-xs font-mono text-white/50 uppercase tracking-widest"
            >
                Agent Profile
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">
                Command Center
            </h1>
        </header>

        <HeroBanner {hero} />
        <AchievementsGrid {achievements} />
        <QuestLedger {ledger} />
        <SystemSettings {preferences} />
    </div>
</Layout>
