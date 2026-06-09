<script>
    import Layout from '../../../layouts/StudentLayout.svelte';
    import AchievementsGrid from '@/components/Profile/AchievementsGrid.svelte';
    import HeroBanner from '@/components/Profile/HeroBanner.svelte';
    import InventoryPanel from '@/components/Profile/InventoryPanel.svelte';
    import QuestLedger from '@/components/Profile/QuestLedger.svelte';
    import SystemSettings from '@/components/Profile/SystemSettings.svelte';

    let { hero, ledger, achievements, inventory, equipped, preferences } = $props();
</script>

<Layout>
    <div class="max-w-2xl mx-auto pb-16 px-4">
        <header class="mb-6">
            <div
                class="inline-block px-4 py-1.5 mb-3 rounded-full border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-xs font-mono text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] uppercase tracking-widest"
            >
                Agent Profile
            </div>
            <h1
                class="text-3xl font-black text-[var(--text-color)] tracking-tight"
            >
                Command Center
            </h1>
        </header>

        <HeroBanner {hero} />
        <InventoryPanel {inventory} {equipped} />
        <AchievementsGrid {achievements} />
        <QuestLedger {ledger} />
        <SystemSettings {preferences} />
    </div>
</Layout>
