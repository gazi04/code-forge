<script>
    import { page } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    const DURATION = 4500;

    /** @type {{ id: number, name: string, description: string|null, image_path: string|null }[]} */
    let queue = $state([]);
    let current = $state(null);
    let visible = $state(false);
    let timer = null;
    let imageError = $state(false);

    $effect(() => {
        const unlocked = page.props.flash?.achievements_unlocked ?? [];
        if (unlocked.length > 0) {
            untrack(() => {
                queue.push(...unlocked);
                if (!visible) {
                    showNext();
                }
            });
        }
    });

    function showNext() {
        if (queue.length === 0) {
            visible = false;
            current = null;
            return;
        }
        current = queue.shift();
        imageError = false;
        visible = true;
        playSound();
        timer = setTimeout(() => {
            visible = false;
            setTimeout(showNext, 400);
        }, DURATION);
    }

    function dismiss() {
        clearTimeout(timer);
        visible = false;
        setTimeout(showNext, 400);
    }

    function getImageSrc(imagePath) {
        if (!imagePath) return null;
        if (
            imagePath.startsWith('http://') ||
            imagePath.startsWith('https://') ||
            imagePath.startsWith('/')
        ) {
            return imagePath;
        }
        return `/storage/${imagePath}`;
    }

    function playSound() {
        try {
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523, ctx.currentTime);
            osc.frequency.setValueAtTime(659, ctx.currentTime + 0.1);
            osc.frequency.setValueAtTime(784, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(
                0.001,
                ctx.currentTime + 0.6,
            );
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.6);
        } catch {
            // Audio not available — silently skip
        }
    }
</script>

{#if visible && current}
    <div
        class="achievement-toast fixed top-6 right-4 sm:right-6 z-[99999] w-80 max-w-[calc(100vw-2rem)]"
        role="alert"
    >
        <div
            class="relative rounded-xl overflow-hidden bg-[var(--surface-color)]"
            style="border: 1px solid color-mix(in srgb, var(--primary-color) 60%, transparent); box-shadow: 0 0 40px color-mix(in srgb, var(--primary-color) 25%, transparent), inset 0 1px 0 color-mix(in srgb, var(--primary-color) 15%, transparent);"
        >
            <!-- Top header bar -->
            <div
                class="flex items-center justify-between px-4 py-2 border-b"
                style="background: linear-gradient(to right, color-mix(in srgb, var(--primary-color) 20%, transparent), color-mix(in srgb, var(--primary-color) 12%, transparent), color-mix(in srgb, var(--primary-color) 20%, transparent)); border-color: color-mix(in srgb, var(--primary-color) 30%, transparent);"
            >
                <div class="flex items-center gap-1.5">
                    <span class="text-[var(--primary-color)] text-xs animate-pulse">✦</span>
                    <span
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--primary-color)]"
                        >Achievement Unlocked</span
                    >
                    <span class="text-[var(--primary-color)] text-xs animate-pulse">✦</span>
                </div>
                <button
                    onclick={dismiss}
                    class="text-[var(--primary-color)] opacity-40 hover:opacity-100 transition-colors text-base leading-none"
                    aria-label="Dismiss">×</button
                >
            </div>

            <!-- Body -->
            <div class="flex items-center gap-4 px-4 py-4">
                <!-- Badge / Image -->
                <div class="shrink-0 relative">
                    <div
                        class="w-14 h-14 rounded-xl flex items-center justify-center overflow-hidden"
                        style="border: 1px solid color-mix(in srgb, var(--primary-color) 40%, transparent); background: color-mix(in srgb, var(--primary-color) 10%, var(--surface-color)); box-shadow: 0 0 16px color-mix(in srgb, var(--primary-color) 30%, transparent);"
                    >
                        {#if current.image_path && !imageError}
                            <img
                                src={getImageSrc(current.image_path)}
                                alt={current.name}
                                class="w-full h-full object-contain p-1"
                                onerror={() => (imageError = true)}
                            />
                        {:else}
                            <span class="text-2xl">🏆</span>
                        {/if}
                    </div>
                    <!-- Corner sparkle -->
                    <span
                        class="absolute -top-1 -right-1 text-[10px] text-[var(--primary-color)] animate-spin"
                        style="animation-duration:3s">✦</span
                    >
                </div>

                <!-- Text -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-black text-[var(--text-color)] leading-tight">
                        {current.name}
                    </p>
                    {#if current.description}
                        <p
                            class="text-[11px] text-[var(--text-color)] opacity-50 mt-1 line-clamp-2 leading-relaxed"
                        >
                            {current.description}
                        </p>
                    {/if}
                </div>
            </div>

            <!-- Progress bar -->
            <div class="h-0.5 bg-[var(--secondary-color)]">
                <div
                    class="progress-bar h-full"
                    style="background: var(--primary-color); box-shadow: 0 0 6px color-mix(in srgb, var(--primary-color) 80%, transparent);"
                ></div>
            </div>
        </div>
    </div>
{/if}

<style>
    .achievement-toast {
        animation: slide-down 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
    }

    @keyframes slide-down {
        0% {
            transform: translateY(-20px) scale(0.95);
            opacity: 0;
        }
        100% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .progress-bar {
        animation: drain 4.5s linear forwards;
        transform-origin: left;
    }

    @keyframes drain {
        from {
            width: 100%;
        }
        to {
            width: 0%;
        }
    }
</style>
