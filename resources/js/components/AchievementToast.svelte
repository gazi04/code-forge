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
        class="achievement-toast fixed top-6 right-6 z-[99999] w-80"
        role="alert"
    >
        <div
            class="relative rounded-xl overflow-hidden border border-yellow-500/60 bg-[#07071a] shadow-[0_0_40px_rgba(234,179,8,0.25),inset_0_1px_0_rgba(234,179,8,0.15)]"
        >
            <!-- Top header bar -->
            <div
                class="flex items-center justify-between px-4 py-2 bg-gradient-to-r from-yellow-500/20 via-amber-400/15 to-yellow-500/20 border-b border-yellow-500/30"
            >
                <div class="flex items-center gap-1.5">
                    <span class="text-yellow-400 text-xs animate-pulse">✦</span>
                    <span
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-yellow-400"
                        >Achievement Unlocked</span
                    >
                    <span class="text-yellow-400 text-xs animate-pulse">✦</span>
                </div>
                <button
                    onclick={dismiss}
                    class="text-yellow-500/40 hover:text-yellow-400 transition-colors text-base leading-none"
                    aria-label="Dismiss">×</button
                >
            </div>

            <!-- Body -->
            <div class="flex items-center gap-4 px-4 py-4">
                <!-- Badge / Image -->
                <div class="shrink-0 relative">
                    <div
                        class="w-14 h-14 rounded-xl border border-yellow-500/40 bg-gradient-to-br from-yellow-950/80 to-amber-950/80 flex items-center justify-center overflow-hidden shadow-[0_0_16px_rgba(234,179,8,0.3)]"
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
                        class="absolute -top-1 -right-1 text-[10px] text-yellow-400 animate-spin"
                        style="animation-duration:3s">✦</span
                    >
                </div>

                <!-- Text -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-black text-white leading-tight">
                        {current.name}
                    </p>
                    {#if current.description}
                        <p
                            class="text-[11px] text-white/50 mt-1 line-clamp-2 leading-relaxed"
                        >
                            {current.description}
                        </p>
                    {/if}
                </div>
            </div>

            <!-- Progress bar -->
            <div class="h-0.5 bg-yellow-950/60">
                <div
                    class="progress-bar h-full bg-gradient-to-r from-yellow-500 to-amber-400 shadow-[0_0_6px_rgba(234,179,8,0.8)]"
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
