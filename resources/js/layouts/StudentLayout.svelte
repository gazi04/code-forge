<script>
    import { page } from '@inertiajs/svelte';
    import AchievementToast from '@/components/AchievementToast.svelte';
    import WorldCompletionModal from '@/components/WorldCompletionModal.svelte';
    import StudentNav from '@/components/StudentNav.svelte';
    import { onMount } from 'svelte';
    import { fade } from 'svelte/transition';
    import confetti from 'canvas-confetti';

    // Prop declaration in Svelte 5 format
    let { theme = null, children } = $props();

    // Reactively watch Inertia's flash data
    let flash = $derived(page.props.flash);
    let user = $derived(page.props.auth.user);

    // Local state for the modals
    let showLevelUpModal = $state(false);
    let newLevel = $state(0);
    let coinBonus = $state(0);

    let showWorldCompletionModal = $state(false);
    let worldCompletion = $state(null);

    // Watch for a level up in the flashed session data
    $effect(() => {
        if (flash?.game_result?.leveled_up) {
            newLevel = flash.game_result.new_level;
            coinBonus = 50;
            showLevelUpModal = true;
            triggerConfetti();
        }
    });

    $effect(() => {
        if (flash?.world_completed) {
            worldCompletion = flash.world_completed;
            showWorldCompletionModal = true;
        }
    });

    function triggerConfetti() {
        const duration = 3000;
        const animationEnd = Date.now() + duration;
        const defaults = {
            startVelocity: 30,
            spread: 360,
            ticks: 60,
            zIndex: 10000,
        };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);

            const particleCount = 50 * (timeLeft / duration);

            confetti(
                Object.assign({}, defaults, {
                    particleCount,
                    origin: {
                        x: randomInRange(0.1, 0.3),
                        y: Math.random() - 0.2,
                    },
                }),
            );
            confetti(
                Object.assign({}, defaults, {
                    particleCount,
                    origin: {
                        x: randomInRange(0.7, 0.9),
                        y: Math.random() - 0.2,
                    },
                }),
            );
        }, 250);
    }

    function getTitle(level) {
        if (level < 5) return 'The Novice';
        if (level < 10) return 'Apprentice Coder';
        if (level < 20) return 'Logic Adept';
        return 'Master Hacker';
    }

    function closeModal() {
        showLevelUpModal = false;
        window.dispatchEvent(new CustomEvent('levelUpClosed'));
    }

    // --- 1. GLOBAL SYSTEM THEME ---
    const GLOBAL = {
        primary: '#8b5cf6',
        secondary: '#0f172a',
        accent: '#10b981',
        background: '#09090b',
        surface: '#18181b',
        text: '#f8fafc',
        font: 'system-ui, sans-serif',
    };

    // --- 2. DYNAMIC WORLD THEME (Migrated perfectly to $derived runes) ---
    let palette = $derived(theme?.config?.palette || {});
    let ui = $derived(theme?.config?.ui || {});
    let bg = $derived(theme?.config?.background || {});
    let audio = $derived(theme?.config?.audio || {});
    let isWorldActive = $derived(!!theme);

    let primary = $derived(palette.primary || GLOBAL.primary);
    let secondary = $derived(palette.secondary || GLOBAL.secondary);
    let accent = $derived(palette.accent || GLOBAL.accent);
    let textColor = $derived(palette.text || GLOBAL.text);
    let surface = $derived(palette.surface || GLOBAL.surface);
    let bgColor = $derived(palette.background || GLOBAL.background);

    const fontStacks = {
        default: 'system-ui, sans-serif',
        monospace: '"Fira Code", Consolas, monospace',
        rounded: '"Quicksand", "Nunito", sans-serif',
        medieval: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
        futuristic: '"Orbitron", "Jura", sans-serif',
    };
    let font = $derived(fontStacks[ui.font_style] || GLOBAL.font);

    const radii = {
        none: '0px',
        sm: '0.25rem',
        md: '0.5rem',
        lg: '1rem',
        full: '9999px',
    };
    let borderRadius = $derived(
        isWorldActive ? radii[ui.border_radius] || radii.md : radii.md,
    );

    let cardBorder = $derived(
        isWorldActive && ui.card_style === 'bordered'
            ? `1px solid ${primary}`
            : isWorldActive && ui.card_style === 'pixel'
              ? `2px solid ${textColor}`
              : '1px solid rgba(255,255,255,0.05)',
    );

    let cardShadow = $derived(
        isWorldActive && ui.card_style === 'embossed'
            ? `inset 2px 2px 5px rgba(255,255,255,0.05), inset -2px -2px 5px rgba(0,0,0,0.5)`
            : isWorldActive && ui.card_style === 'pixel'
              ? `4px 4px 0px ${primary}`
              : `0 4px 20px -2px rgba(0, 0, 0, 0.4)`,
    );

    let cardBackdrop = $derived(
        isWorldActive && ui.card_style === 'glassy' ? 'blur(16px)' : 'none',
    );
    let computedSurfaceBg = $derived(
        isWorldActive && ui.card_style === 'glassy'
            ? `color-mix(in srgb, ${surface} 60%, transparent)`
            : surface,
    );

    let bgType = $derived(bg.style || 'solid');
    let bgVal = $derived(bg.value || '');
    let isImageUrl = $derived(bgType === 'image' || bgType === 'pattern');

    let computedBgColor = $derived(
        isWorldActive
            ? bgType === 'solid'
                ? bgVal
                : bgColor
            : GLOBAL.background,
    );
    let bgImage = $derived(
        isWorldActive && isImageUrl && bgVal
            ? `url('${bgVal}')`
            : isWorldActive && bgType === 'gradient'
              ? bgVal
              : 'none',
    );
    let bgSize = $derived(bgType === 'pattern' ? 'auto' : 'cover');
    let bgRepeat = $derived(bgType === 'pattern' ? 'repeat' : 'no-repeat');

    let cssVariables = $derived(`
        --primary-color: ${primary};
        --secondary-color: ${secondary};
        --accent-color: ${accent};
        --bg-color: ${computedBgColor};
        --surface-color: ${computedSurfaceBg};
        --text-color: ${textColor};
        --font-main: ${font};
        --border-radius: ${borderRadius};
        --card-border: ${cardBorder};
        --card-shadow: ${cardShadow};
        --card-backdrop: ${cardBackdrop};
        --env-bg-size: ${bgSize};
        --env-bg-repeat: ${bgRepeat};
    `);
</script>

{#if audio.background_music_url}
    <audio
        id="world-bgm"
        src={audio.background_music_url}
        loop
        autoplay
        class="hidden"
    ></audio>
{/if}

<div class="layout-container" style={cssVariables}>
    {#if isWorldActive && bgImage !== 'none'}
        {#key bgImage}
            <div
                class="environmental-bg"
                style="background-image: {bgImage};"
                transition:fade={{ duration: 1000 }}
            ></div>
        {/key}
    {/if}

    <StudentNav {user} />

    <main class="content-wrapper">
        {#key isWorldActive}
            <div
                in:fade={{ duration: 400, delay: 150 }}
                out:fade={{ duration: 300 }}
            >
                <slot />
            </div>
        {/key}
    </main>

    <AchievementToast />

    {#if showWorldCompletionModal && worldCompletion}
        <WorldCompletionModal
            worldSlug={worldCompletion.world_slug}
            worldName={worldCompletion.world_name}
            bonusXp={worldCompletion.bonus_xp}
            bonusCoins={worldCompletion.bonus_coins}
            onclose={() => {
                showWorldCompletionModal = false;
                window.dispatchEvent(new CustomEvent('worldCompletionClosed'));
            }}
        />
    {/if}

    {#if showLevelUpModal}
        <div
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-md transition-all"
        >
            <div
                class="rounded-3xl p-10 max-w-md w-full text-center animate-bounce-in bg-[var(--surface-color)] border-4 border-[var(--primary-color)]"
                style="box-shadow: 0 0 50px color-mix(in srgb, var(--primary-color) 40%, transparent);"
            >
                <h2
                    class="text-5xl font-black mb-2 drop-shadow-lg text-[var(--primary-color)]"
                >
                    LEVEL UP!
                </h2>

                <div
                    class="w-32 h-32 mx-auto my-6 rounded-full flex items-center justify-center shadow-inner bg-[var(--secondary-color)] border-4 border-[var(--primary-color)]"
                >
                    <span class="text-6xl font-black text-[var(--text-color)]"
                        >{newLevel}</span
                    >
                </div>

                <h3 class="text-2xl font-bold text-[var(--text-color)] mb-1">
                    {getTitle(newLevel)}
                </h3>
                <p class="text-[var(--text-color)] opacity-60 mb-8 font-mono text-sm">
                    Your logical reasoning has expanded.<br />
                    <span class="font-bold block mt-2 opacity-100 text-[var(--primary-color)]"
                        >+ {coinBonus} Gold Coins</span
                    >
                </p>

                <button
                    onclick={closeModal}
                    class="w-full font-black uppercase tracking-widest py-4 rounded-xl transition-all transform hover:scale-105 active:scale-95 text-[var(--bg-color)]"
                    style="background: var(--primary-color); box-shadow: 0 0 15px color-mix(in srgb, var(--primary-color) 50%, transparent);"
                >
                    Continue Journey
                </button>
            </div>
        </div>
    {/if}
</div>

<style>
    .layout-container {
        min-height: 100vh;
        background-color: var(--bg-color);
        font-family: var(--font-main);
        color: var(--text-color);
        transition:
            background-color 0.8s cubic-bezier(0.4, 0, 0.2, 1),
            color 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .environmental-bg {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        opacity: 0.15;
        background-size: var(--env-bg-size);
        background-repeat: var(--env-bg-repeat);
        background-position: center;
        mask-image: linear-gradient(
            to bottom,
            rgba(0, 0, 0, 1) 0%,
            rgba(0, 0, 0, 0.2) 100%
        );
        -webkit-mask-image: linear-gradient(
            to bottom,
            rgba(0, 0, 0, 1) 0%,
            rgba(0, 0, 0, 0.2) 100%
        );
    }

    .content-wrapper {
        position: relative;
        z-index: 10;
        max-width: 80rem;
        margin: 0 auto;
        padding: 2rem 1rem 6rem 1rem;
    }

    /* Structural classes universally tied to an 0.8s ease to prevent shape/color snapping */
    :global(.text-primary) {
        color: var(--primary-color);
        transition: color 0.8s ease;
    }
    :global(.text-accent) {
        color: var(--accent-color);
        transition: color 0.8s ease;
    }
    :global(.bg-primary) {
        background-color: var(--primary-color);
        transition: background-color 0.8s ease;
    }

    :global(.bg-surface) {
        background-color: var(--surface-color);
        border-radius: var(--border-radius);
        border: var(--card-border);
        box-shadow: var(--card-shadow);
        backdrop-filter: var(--card-backdrop);
        -webkit-backdrop-filter: var(--card-backdrop);
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

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
