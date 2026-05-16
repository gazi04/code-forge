<script>
  import { onMount } from 'svelte';

  export let theme = null;

  // Safely extract nested config objects
  $: palette = theme?.config?.palette || {};
  $: ui = theme?.config?.ui || {};
  $: bg = theme?.config?.background || {};
  $: audio = theme?.config?.audio || {};

  // 1. COLORS
  $: primary = palette.primary || '#3b82f6';
  $: secondary = palette.secondary || '#0f172a';
  $: accent = palette.accent || '#10b981';
  $: bgColor = palette.background || '#111827';
  $: surface = palette.surface || '#1f2937';
  $: textColor = palette.text || '#ffffff';

  // 2. TYPOGRAPHY (ui.font_style)
  const fontStacks = {
    default: 'system-ui, sans-serif',
    monospace: '"Fira Code", Consolas, monospace',
    rounded: '"Quicksand", "Nunito", sans-serif',
    medieval: '"Palatino Linotype", "Book Antiqua", Palatino, serif',
    futuristic: '"Orbitron", "Jura", sans-serif'
  };
  $: font = fontStacks[ui.font_style] || fontStacks.default;

  // 3. SHAPE (ui.border_radius)
  const radii = {
    none: '0px',
    sm: '0.25rem',
    md: '0.5rem',
    lg: '1rem',
    full: '9999px'
  };
  $: borderRadius = radii[ui.border_radius] || radii.md;

  // 4. CARD STYLES (ui.card_style)
  // We compute specific CSS properties to feed into our global .bg-surface utility
  $: cardBorder = ui.card_style === 'bordered' ? `2px solid ${primary}`
                : ui.card_style === 'pixel' ? `2px solid ${textColor}`
                : '1px solid transparent';

  $: cardShadow = ui.card_style === 'embossed' ? `inset 2px 2px 5px rgba(255,255,255,0.1), inset -2px -2px 5px rgba(0,0,0,0.5)`
                : ui.card_style === 'pixel' ? `4px 4px 0px ${primary}`
                : '0 4px 6px -1px rgba(0, 0, 0, 0.1)';

  $: cardBackdrop = ui.card_style === 'glassy' ? 'blur(12px)' : 'none';

  // For glassy, we need to make the surface color semi-transparent
  $: computedSurfaceBg = ui.card_style === 'glassy'
    ? `color-mix(in srgb, ${surface} 40%, transparent)`
    : surface;

  // 5. BACKGROUND ENGINE (background.style & background.value)
  $: bgType = bg.style || 'solid';
  $: bgVal = bg.value || '';

  // Determine CSS properties based on the type
  $: isImageUrl = bgType === 'image' || bgType === 'pattern';
  $: bgImage = isImageUrl ? `url('${bgVal}')` : (bgType === 'gradient' ? bgVal : 'none');
  $: computedBgColor = bgType === 'solid' ? bgVal : bgColor;
  $: bgSize = bgType === 'pattern' ? 'auto' : 'cover';
  $: bgRepeat = bgType === 'pattern' ? 'repeat' : 'no-repeat';

  // Consolidate into a single style string
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
    --env-bg-image: ${bgImage};
    --env-bg-size: ${bgSize};
    --env-bg-repeat: ${bgRepeat};
  `;
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
  <div class="environmental-bg"></div>

  <main class="content-wrapper">
    <slot />
  </main>
</div>

<style>
  .layout-container {
    min-height: 100vh;
    background-color: var(--bg-color);
    font-family: var(--font-main);
    color: var(--text-color);
    transition: background-color 0.8s ease, font-family 0.3s ease;
  }

  .environmental-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    opacity: 0.3; /* Adjust based on how heavy your images are */
    background-image: var(--env-bg-image);
    background-size: var(--env-bg-size);
    background-repeat: var(--env-bg-repeat);
    background-position: center;
    transition: background-image 1s ease-in-out;
  }

  .content-wrapper {
    position: relative;
    z-index: 10;
    max-width: 80rem;
    margin: 0 auto;
    padding: 2rem;
  }

  /* Structural Global Utilities */
  :global(.text-primary) { color: var(--primary-color); }
  :global(.text-accent) { color: var(--accent-color); }
  :global(.bg-primary) { background-color: var(--primary-color); }

  /* The upgraded surface utility now handles border-radius, glassmorphism, and retro borders */
  :global(.bg-surface) {
    background-color: var(--surface-color);
    border-radius: var(--border-radius);
    border: var(--card-border);
    box-shadow: var(--card-shadow);
    backdrop-filter: var(--card-backdrop);
    -webkit-backdrop-filter: var(--card-backdrop);
  }
</style>
