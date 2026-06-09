<script>
    import { useForm } from '@inertiajs/svelte';

    let { preferences } = $props();

    const form = useForm({
        background_audio: preferences.background_audio,
        sound_effects: preferences.sound_effects,
        accessibility_mode: preferences.accessibility_mode,
    });

    function toggle(key) {
        form[key] = !form[key];
        form.put('/profile/settings', { preserveScroll: true });
    }
</script>

<div class="bg-surface rounded-2xl p-6">
    <h2 class="text-base font-black text-[var(--text-color)] tracking-tight mb-5">
        System Settings
    </h2>

    <div class="space-y-3">
        {#each [{ key: 'background_audio', label: 'Background Audio', icon: '🎵', description: 'Play ambient music during lessons' }, { key: 'sound_effects', label: 'Sound Effects', icon: '🔊', description: 'Play sounds on interactions and rewards' }, { key: 'accessibility_mode', label: 'Accessibility Mode', icon: '♿', description: 'Increase contrast and reduce motion' }] as setting}
            <div
                class="flex items-center justify-between gap-4 p-4 rounded-xl bg-[color-mix(in_srgb,var(--text-color)_4%,transparent)] border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)]"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xl leading-none">{setting.icon}</span>
                    <div>
                        <p class="text-base font-semibold text-[var(--text-color)]">
                            {setting.label}
                        </p>
                        <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] font-mono mt-0.5">
                            {setting.description}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    role="switch"
                    aria-checked={form[setting.key]}
                    onclick={() => toggle(setting.key)}
                    disabled={form.processing}
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-offset-2 focus:ring-offset-black disabled:opacity-50
                        {form[setting.key]
                        ? 'bg-[var(--primary-color)]'
                        : 'bg-white/10'}"
                >
                    <span
                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-300
                            {form[setting.key]
                            ? 'translate-x-6'
                            : 'translate-x-1'}"
                    ></span>
                </button>
            </div>
        {/each}
    </div>
</div>
