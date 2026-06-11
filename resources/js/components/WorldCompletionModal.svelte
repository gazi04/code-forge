<script>
    import { certificate } from '@/actions/App/Http/Controllers/WorldController';
    import confetti from 'canvas-confetti';
    import { onMount } from 'svelte';

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
        class="animate-bounce-in bg-zinc-900 border-4 border-yellow-400 rounded-3xl p-10 max-w-md w-full text-center shadow-[0_0_60px_rgba(250,204,21,0.45)] mx-4"
    >
        <div class="text-6xl mb-3">🏆</div>

        <h2
            class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-b from-yellow-300 to-orange-500 mb-1 drop-shadow-lg"
        >
            World Complete!
        </h2>

        <p class="text-slate-400 text-sm mb-4 font-mono">
            You conquered
        </p>

        <div
            class="text-2xl font-bold text-white mb-6 px-4 py-2 rounded-xl bg-zinc-800 border border-yellow-400/30 inline-block"
        >
            {worldName}
        </div>

        <div class="flex justify-center gap-6 mb-8">
            <div class="text-center">
                <div class="text-yellow-400 font-black text-xl">+{bonusXp}</div>
                <div class="text-slate-400 text-xs font-mono uppercase tracking-wider">Bonus XP</div>
            </div>
            <div class="w-px bg-zinc-700"></div>
            <div class="text-center">
                <div class="text-amber-400 font-black text-xl">+{bonusCoins}</div>
                <div class="text-slate-400 text-xs font-mono uppercase tracking-wider">Coins</div>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <a
                href={certificateUrl}
                target="_blank"
                rel="noopener noreferrer"
                class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 text-black font-black uppercase tracking-widest py-3 rounded-xl shadow-[0_0_15px_rgba(250,204,21,0.4)] transition-all transform hover:scale-105 active:scale-95 text-sm"
            >
                📜 Download Certificate
            </a>

            <button
                onclick={onclose}
                class="w-full bg-zinc-800 hover:bg-zinc-700 text-white font-bold uppercase tracking-widest py-3 rounded-xl border border-zinc-600 transition-all transform hover:scale-105 active:scale-95 text-sm"
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
