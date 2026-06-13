<script>
    import { certificate } from '@/actions/App/Http/Controllers/WorldController';

    let { certificates = [], isPublic = false } = $props();

    function formatDate(dateStr) {
        if (!dateStr) {
return '';
}

        return new Date(dateStr).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }
</script>

<div class="bg-surface rounded-2xl p-6 mb-6">
    <div class="flex items-baseline justify-between mb-5">
        <h2 class="text-lg font-black text-[var(--text-color)] tracking-tight">
            Certificates
        </h2>
        {#if certificates.length > 0}
            <span
                class="text-xs font-mono font-bold px-2.5 py-1 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_12%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_30%,transparent)] text-[var(--primary-color)] transition-colors duration-500"
            >
                {certificates.length} earned
            </span>
        {/if}
    </div>

    {#if certificates.length === 0}
        <div class="text-center py-14">
            <p
                class="text-sm font-black uppercase tracking-widest text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] mb-1"
            >
                No certificates yet
            </p>
            <p
                class="text-sm text-[color-mix(in_srgb,var(--text-color)_20%,transparent)]"
            >
                Complete every lesson in a world to earn your certificate.
            </p>
        </div>
    {:else}
        <div class="space-y-3">
            {#each certificates as cert (cert.world_slug)}
                <div
                    class="cert-row group flex items-center gap-4 px-4 py-4 rounded-xl bg-[color-mix(in_srgb,var(--text-color)_3%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_7%,transparent)] border-l-2"
                    style="border-left-color: {cert.primary_color};"
                >
                    <!-- World badge -->
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                        style="background-color: color-mix(in srgb, {cert.primary_color} 12%, transparent); border-color: color-mix(in srgb, {cert.primary_color} 30%, transparent);"
                    >
                        <span
                            class="text-lg font-black"
                            style="color: {cert.primary_color};"
                        >◆</span>
                    </div>

                    <!-- World name + date -->
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-sm font-bold text-[var(--text-color)] leading-tight truncate"
                        >
                            {cert.world_name}
                        </p>
                        <time
                            class="text-xs font-mono text-[color-mix(in_srgb,var(--text-color)_35%,transparent)]"
                        >
                            Completed {formatDate(cert.completed_at)}
                        </time>
                    </div>

                    <!-- Bonus rewards -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div
                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] transition-colors duration-500"
                        >
                            <span class="text-xs">✨</span>
                            <span
                                class="text-xs font-black font-mono text-[var(--primary-color)]"
                                >+{cert.xp_bonus}</span
                            >
                        </div>
                        <div
                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-yellow-400/10 border border-yellow-400/20"
                        >
                            <span class="text-xs">💰</span>
                            <span class="text-xs font-black font-mono text-yellow-400"
                                >+{cert.coins_bonus}</span
                            >
                        </div>
                    </div>

                    <!-- Download button (owner only) -->
                    {#if !isPublic}
                        <a
                            href={certificate.url({ world: cert.world_slug })}
                            target="_blank"
                            rel="noopener noreferrer"
                            class="download-btn shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-bold uppercase tracking-wider transition-all duration-200"
                            style="color: {cert.primary_color}; border-color: color-mix(in srgb, {cert.primary_color} 30%, transparent); background-color: color-mix(in srgb, {cert.primary_color} 8%, transparent);"
                        >
                            <span class="text-[11px]">↓</span> PDF
                        </a>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}
</div>

<style>
    .cert-row {
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            border-color 0.2s ease;
    }
    .cert-row:hover {
        transform: translateX(2px);
    }
    .download-btn:hover {
        filter: brightness(1.2);
    }
</style>
