<script>
  import { marked } from 'marked';

  let { data } = $props();

  const renderedBody = $derived(marked.parse(data.content || ''));
</script>

<div class="prose max-w-none bg-surface p-8
            [--tw-prose-body:var(--text-color)]
            [--tw-prose-headings:var(--text-color)]
            [--tw-prose-bold:var(--text-color)]
            [--tw-prose-links:var(--primary-color)]
            [--tw-prose-bullets:var(--primary-color)]
            [--tw-prose-counters:var(--primary-color)]
            [--tw-prose-quotes:var(--text-color)]
            [--tw-prose-code:var(--accent-color)]
            [--tw-prose-hr:color-mix(in_srgb,var(--text-color)_20%,transparent)]
            transition-colors duration-800">
  {@html renderedBody}
</div>
