<script>
  import { onMount } from 'svelte';
  import { Link } from '@inertiajs/svelte';
  import { fade } from 'svelte/transition'; // Required for the crossfade engine

  export let theme = null;

  // --- 1. GLOBAL SYSTEM THEME ---
  const GLOBAL = {
    primary: '#8b5cf6',
    secondary: '#0f172a',
    accent: '#10b981',
    background: '#09090b',
    surface: '#18181b',
    text: '#f8fafc',
    font: 'system-ui, sans-serif'
  };

  // --- 2. DYNAMIC WORLD THEME ---
  $: palette = theme?.config?.palette || {};
  $: ui = theme?.config?.ui || {};
  $: bg = theme?.config?.background || {};
  $: audio = theme?.config?.audio || {};

  $: isWorldActive = !!theme;

  $: primary = palette.primary || GLOBAL.primary;
  $: secondary = palette.secondary || GLOBAL.secondary;
  $: accent = palette.accent || GLOBAL.accent;
  $: textColor = palette.text || GLOBAL.text;

  $: bgColor = palette.background || GLOBAL.background;
  $: surface = palette.surface || GLOBAL.surface;

  const fontStacks = {
    default: 'system-ui, sans-serif',
    monospace: '"Fira Code", Consolas, monospace',
    rounded: '"Quicksand", "Nunito", sans-serif',
    medieval: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
    futuristic: '"Orbitron", "Jura", sans-serif'
  };
  $: font = fontStacks[ui.font_style] || GLOBAL.font;

  const radii = { none: '0px', sm: '0.25rem', md: '0.5rem', lg: '1rem', full: '9999px' };
  $: borderRadius = isWorldActive ? (radii[ui.border_radius] || radii.md) : radii.md;

  $: cardBorder = isWorldActive && ui.card_style === 'bordered' ? `1px solid ${primary}`
                : isWorldActive && ui.card_style === 'pixel' ? `2px solid ${textColor}`
                : '1px solid rgba(255,255,255,0.05)';

  $: cardShadow = isWorldActive && ui.card_style === 'embossed' ? `inset 2px 2px 5px rgba(255,255,255,0.05), inset -2px -2px 5px rgba(0,0,0,0.5)`
                : isWorldActive && ui.card_style === 'pixel' ? `4px 4px 0px ${primary}`
                : `0 4px 20px -2px rgba(0, 0, 0, 0.4)`;

  $: cardBackdrop = isWorldActive && ui.card_style === 'glassy' ? 'blur(16px)' : 'none';

  $: computedSurfaceBg = isWorldActive && ui.card_style === 'glassy'
    ? `color-mix(in srgb, ${surface} 60%, transparent)`
    : surface;

  $: bgType = bg.style || 'solid';
  $: bgVal = bg.value || '';
  $: isImageUrl = bgType === 'image' || bgType === 'pattern';

  $: computedBgColor = isWorldActive ? (bgType === 'solid' ? bgVal : bgColor) : GLOBAL.background;
  $: bgImage = isWorldActive && isImageUrl && bgVal ? `url('${bgVal}')` : (isWorldActive && bgType === 'gradient' ? bgVal : 'none');
  $: bgSize = bgType === 'pattern' ? 'auto' : 'cover';
  $: bgRepeat = bgType === 'pattern' ? 'repeat' : 'no-repeat';

  $: cssVariables = `
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
  `;
</script>

{#if audio.background_music_url}
  <audio id="world-bgm" src={audio.background_music_url} loop autoplay class="hidden"></audio>
{/if}

<div class="layout-container" style={cssVariables}>

  {#if isWorldActive && bgImage !== 'none'}
    {#key bgImage}
      <div class="environmental-bg"
           style="background-image: {bgImage};"
           transition:fade={{ duration: 1000 }}>
      </div>
    {/key}
  {/if}

  <nav class="sticky top-0 z-50 border-b border-white/10 bg-black/60 backdrop-blur-2xl text-white transition-colors duration-500">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
      <div class="flex items-center gap-6">
        <Link href="/worlds" class="font-bold tracking-widest uppercase text-sm opacity-80 hover:text-[var(--primary-color)] hover:opacity-100 transition-colors">
          Arcane.dev
        </Link>
      </div>
      <div class="flex items-center gap-4 text-sm font-medium">
        <div class="px-3 py-1 rounded-md bg-white/5 border border-white/10 flex items-center gap-2">
          <span class="opacity-50">System</span> <span class="text-[var(--accent-color)] transition-colors duration-500">Online</span>
        </div>
      </div>
    </div>
  </nav>

  <main class="content-wrapper">
    {#key isWorldActive}
      <div in:fade={{ duration: 400, delay: 150 }} out:fade={{ duration: 300 }}>
        <slot />
      </div>
    {/key}
  </main>
</div>

<style>
  .layout-container {
    min-height: 100vh;
    background-color: var(--bg-color);
    font-family: var(--font-main);
    color: var(--text-color);
    transition: background-color 0.8s cubic-bezier(0.4, 0, 0.2, 1),
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
    mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0.2) 100%);
    -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0.2) 100%);
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
</style>
