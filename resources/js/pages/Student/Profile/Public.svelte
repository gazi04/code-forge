<script>
    import AchievementsGrid from '@/components/Profile/AchievementsGrid.svelte';
    import CertificatesPanel from '@/components/Profile/CertificatesPanel.svelte';
    import HeroBanner from '@/components/Profile/HeroBanner.svelte';
    import StudentLayout from '@/layouts/StudentLayout.svelte';

    let { hero, achievements = [], certificates = [] } = $props();
</script>

<StudentLayout>
    <div class="max-w-2xl mx-auto pb-16 px-4">
        <header class="mb-6">
            <h1
                class="text-2xl sm:text-3xl font-black text-[var(--text-color)] tracking-tight"
            >
                {hero.name}
            </h1>
        </header>

        <HeroBanner {hero} />
        {#if certificates.length > 0}
            <CertificatesPanel {certificates} isPublic={true} />
        {/if}
        <AchievementsGrid {achievements} />
    </div>
</StudentLayout>
