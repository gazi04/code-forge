<script>
  export let theme = null;

  // Safely extract nested config objects
  $: palette = theme?.config?.palette || {};
  $: ui = theme?.config?.ui || {};
  $: bg = theme?.config?.background || {};

  // Extract variables with global "Lobby" fallbacks
  $: primary = palette.primary || '#3b82f6';
  $: secondary = palette.secondary || '#0f172a';
  $: accent = palette.accent || '#10b981';
  $: bgColor = palette.background || '#111827';
  $: surface = palette.surface || '#1f2937';
  $: textColor = palette.text || '#ffffff';

  // Map the font_style (e.g., 'medieval' maps to a serif stack)
  $: font = ui.font_style === 'medieval' ? '"Palatino Linotype", "Book Antiqua", Palatino, serif' : 'system-ui, sans-serif';

  // Handle the background value (wrap in url() if it's an image link)
  $: bgPattern = bg.value && bg.value.startsWith('http') ? `url('${bg.value}')` : 'none';

  // Consolidate into a single style string
  $: cssVariables = `
    --primary-color: ${primary};
    --secondary-color: ${secondary};
    --accent-color: ${accent};
    --bg-color: ${bgColor};
    --surface-color: ${surface};
    --text-color: ${textColor};
    --font-main: ${font};
    --bg-pattern: ${bgPattern};
  `;
</script>

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
    transition: background-color 0.8s ease;
  }

  .environmental-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    opacity: 0.25;
    background-image: var(--bg-pattern);
    background-size: cover;
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

  /* Expose custom utilities mapped to the dynamic palette */
  :global(.text-primary) { color: var(--primary-color); }
  :global(.text-accent) { color: var(--accent-color); }
  :global(.bg-primary) { background-color: var(--primary-color); }
  :global(.bg-surface) { background-color: var(--surface-color); }
  :global(.border-primary) { border-color: var(--primary-color); }
</style>
