<script>
    import confetti from 'canvas-confetti';
    import { onMount } from 'svelte';
    import { certificate } from '@/actions/App/Http/Controllers/WorldController';

    let {
        worldSlug,
        worldName,
        bonusXp,
        bonusCoins,
        onclose,
    } = $props();

    onMount(() => {
        triggerConfetti();
    });

    function triggerConfetti() {
        const duration = 4000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10000 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            const particleCount = 50 * (timeLeft / duration);

            confetti(
                Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                }),
            );
            confetti(
                Object.assign({}, defaults, {
                    particleCount,
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                }),
            );
        }, 250);
    }

    let certificateUrl = $derived(worldSlug ? certificate.url({ world: worldSlug }) : '#');
</script>

<div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-md"
>
    <div
        class="animate-bounce-in rounded-3xl p-6 sm:p-10 max-w-md w-full max-h-[90dvh] overflow-y-auto text-center mx-4 bg-[var(--surface-color)] border-4 border-[var(--primary-color)]"
        style="box-shadow: 0 0 60px color-mix(in srgb, var(--primary-color) 45%, transparent);"
    >
        <div class="text-6xl mb-3">🏆</div>

        <h2
            class="text-4xl font-black mb-1 drop-shadow-lg text-[var(--primary-color)]"
        >
            World Complete!
        </h2>

        <p class="text-[var(--text-color)] opacity-50 text-sm mb-4 font-mono">
            You conquered
        </p>

        <div
            class="text-2xl font-bold mb-6 px-4 py-2 rounded-xl inline-block text-[var(--text-color)] bg-[var(--secondary-color)]"
            style="border: 1px solid color-mix(in srgb, var(--primary-color) 30%, transparent);"
        >
            {worldName}
        </div>

        <div class="flex justify-center gap-6 mb-8">
            <div class="text-center">
                <div class="font-black text-xl text-[var(--primary-color)]">+{bonusXp}</div>
                <div class="text-[var(--text-color)] opacity-50 text-xs font-mono uppercase tracking-wider">Bonus XP</div>
            </div>
            <div class="w-px bg-[var(--primary-color)] opacity-20"></div>
            <div class="text-center">
                <div class="text-amber-400 font-black text-xl">+{bonusCoins}</div>
                <div class="text-[var(--text-color)] opacity-50 text-xs font-mono uppercase tracking-wider">Coins</div>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <a
                href={certificateUrl}
                target="_blank"
                rel="noopener noreferrer"
                class="w-full flex items-center justify-center gap-2 font-black uppercase tracking-widest py-3 rounded-xl transition-all transform hover:scale-105 active:scale-95 text-sm text-[var(--bg-color)]"
                style="background: var(--primary-color); box-shadow: 0 0 15px color-mix(in srgb, var(--primary-color) 40%, transparent);"
            >
                📜 Download Certificate
            </a>

            <button
                onclick={onclose}
                class="w-full font-bold uppercase tracking-widest py-3 rounded-xl transition-all transform hover:scale-105 active:scale-95 text-sm text-[var(--text-color)] bg-[var(--secondary-color)]"
                style="border: 1px solid color-mix(in srgb, var(--text-color) 15%, transparent);"
            >
                Continue Journey
            </button>
        </div>
    </div>
</div>

<style>
    .animate-bounce-in {
        animation: bounce-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
    }

    @keyframes bounce-in {
        0% {
            transform: scale(0.3);
            opacity: 0;
        }
        50% {
            transform: scale(1.05);
            opacity: 1;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>
