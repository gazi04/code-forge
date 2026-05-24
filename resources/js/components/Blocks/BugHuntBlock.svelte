<script>
  import { onMount } from 'svelte';
  let { data, index } = $props();

  // State Management via Svelte 5 Runes
  let processedLines = $state([]);
  let activeLineIdx = $state(null);
  let bugsRemaining = $state(0);
  let isCleared = $state(false);
  let feedbackMsg = $state("Inspect the codebase thoroughly. Click any line to analyze its state.");
  let feedbackStatus = $state("info"); // info, success, warning

  onMount(() => {
    initializeChallenge();
  });

  function initializeChallenge() {
    let internalBugs = 0;

    processedLines = data.code_lines.map((line, idx) => {
      let isBuggy = line.type === 'buggy';
      let options = [];

      if (isBuggy) {
        internalBugs++;
        // Combine real bug text, correct answer, and decoys, then shuffle them
        let choices = [line.displayed_text, line.correct_text, line.decoy_1, line.decoy_2];
        for (let i = choices.length - 1; i > 0; i--) {
          const j = Math.floor(Math.random() * (i + 1));
          [choices[i], choices[j]] = [choices[j], choices[i]];
        }
        options = choices;
      }

      return {
        id: idx,
        type: line.type,
        initialText: line.displayed_text,
        correctText: isBuggy ? line.correct_text : line.displayed_text,
        currentText: line.displayed_text,
        choices: options,
        isFixed: !isBuggy
      };
    });

    bugsRemaining = internalBugs;
    activeLineIdx = null;
    isCleared = false;
    feedbackMsg = "Inspect the codebase thoroughly. Click any line to analyze its state.";
    feedbackStatus = "info";
  }

  function handleLineClick(idx) {
    if (isCleared) return;

    // Toggle selector drawer open/closed
    if (activeLineIdx === idx) {
      activeLineIdx = null;
    } else {
      activeLineIdx = idx;
      const targetLine = processedLines[idx];

      if (targetLine.type === 'clean') {
        feedbackMsg = `⚡ Line ${idx + 1} looks clean! No syntax abnormalities detected here.`;
        feedbackStatus = "info";
      } else if (targetLine.isFixed) {
        feedbackMsg = `✅ Line ${idx + 1} has already been patched and resolved.`;
        feedbackStatus = "success";
      } else {
        feedbackMsg = `🔍 Line ${idx + 1} seems corrupted. Select an automated hotfix payload below!`;
        feedbackStatus = "warning";
      }
    }
  }

  function applyHotfix(lineIdx, selectedOption) {
    let line = processedLines[lineIdx];
    line.currentText = selectedOption;

    if (selectedOption === line.correctText) {
      if (!line.isFixed) {
        line.isFixed = true;
        bugsRemaining--;
      }
    } else {
      // If they changed it to an alternate wrong text, mark it unfixed
      if (line.isFixed) {
        line.isFixed = false;
        bugsRemaining++;
      }
    }

    activeLineIdx = null; // Close option tray
    checkWinCondition();
  }

  function checkWinCondition() {
    if (bugsRemaining === 0) {
      isCleared = true;
      feedbackMsg = "🎉 Integrity Restored! All hidden compilation anomalies have been purged successfully.";
      feedbackStatus = "success";
    } else {
      feedbackMsg = `Patch deployed. Remaining runtime exceptions tracking count: ${bugsRemaining}.`;
      feedbackStatus = "info";
    }
  }
</script>

<div class="w-full bg-[#0d071d] rounded-2xl border border-indigo-900/50 shadow-2xl mt-8 overflow-hidden font-sans">

  <div class="bg-[#150b2e] px-6 py-4 border-b border-indigo-900/50 flex justify-between items-center">
    <div class="flex items-center gap-4">
      <div class="w-10 h-10 rounded-full bg-indigo-950 border border-indigo-500/30 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(99,102,241,0.2)]">
        {data.game_icon || '🐛'}
      </div>
      <div>
        <h4 class="font-serif font-bold text-indigo-100 text-lg tracking-wide">{data.game_title}</h4>
        <p class="text-xs text-indigo-300/60 font-mono mt-0.5">{data.instructions}</p>
      </div>
    </div>
    {#if data.is_required && !isCleared}
      <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-purple-900/30 text-purple-300 border border-purple-700/50 uppercase tracking-widest">
        Required Quest
      </span>
    {/if}
  </div>

  <div class="p-6 w-full">

    <div class="flex justify-between items-center mb-4 text-xs font-mono">
      <div class="px-3 py-1.5 bg-[#150b2e] border border-indigo-900/50 text-indigo-300 rounded-md">
        Anomalies Found: <span class="font-bold text-white">{isCleared ? '0' : bugsRemaining}</span>
      </div>

      <button onclick={initializeChallenge} disabled={isCleared}
        class="text-[10px] uppercase tracking-widest font-bold text-indigo-300/70 hover:text-indigo-200 transition-colors disabled:opacity-30">
        ↺ Reload Original Sandbox
      </button>
    </div>

    <div class="w-full bg-[#05020a] rounded-xl border border-indigo-950/80 shadow-inner p-4 overflow-hidden flex flex-col font-mono text-sm leading-relaxed">

      {#each processedLines as line, idx (line.id)}
        {@const isLineActive = activeLineIdx === idx}
        {@const isLineBuggyAndUnresolved = line.type === 'buggy' && !line.isFixed}

        <div
          onclick={() => handleLineClick(idx)}
          class="group w-full flex items-start cursor-pointer transition-colors border-l-2 py-0.5
            {isLineActive ? 'bg-indigo-950/20 border-indigo-500' : 'border-transparent hover:bg-indigo-950/10'}"
        >
          <div class="w-10 select-none text-right pr-4 text-indigo-950 group-hover:text-indigo-700/50 transition-colors font-bold text-xs pt-0.5">
            {idx + 1}
          </div>

          <div class="flex-1 whitespace-pre-wrap tracking-wide transition-colors
            {line.type === 'buggy' && line.isFixed ? 'text-emerald-400' : isLineBuggyAndUnresolved ? 'text-indigo-100 group-hover:text-purple-300' : 'text-indigo-300/80'}">
            {line.currentText}
          </div>

          <div class="px-3 text-xs select-none">
            {#if line.type === 'buggy' && line.isFixed}
              <span class="text-emerald-500/80">✨ Patched</span>
            {:else if isLineBuggyAndUnresolved && line.currentText !== line.initialText}
              <span class="text-rose-400 font-bold animate-pulse">⚠️ Warning</span>
            {/if}
          </div>
        </div>

        {#if isLineActive && line.type === 'buggy' && !line.isFixed}
          <div class="w-full pl-10 pr-4 py-3 bg-[#0d071d]/60 border-y border-indigo-950 flex flex-col gap-2 my-1 animate-fadeIn">
            <span class="text-[10px] text-purple-400 uppercase tracking-widest font-bold block mb-1">Select Patch Modification:</span>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              {#each line.choices as option}
                <button
                  onclick={() => applyHotfix(idx, option)}
                  class="w-full text-left p-2.5 rounded-lg border text-xs transition-all bg-[#0a0515] font-mono text-indigo-200 border-indigo-950 hover:border-purple-500/60 hover:bg-[#150b2e]
                    {line.currentText === option ? 'border-purple-500 bg-[#150b2e] text-purple-300' : ''}"
                >
                  {option}
                </button>
              {/each}
            </div>
          </div>
        {/if}

      {/each}

    </div>

    <div class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center border transition-all duration-300
      {feedbackStatus === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800/50' : ''}
      {feedbackStatus === 'warning' ? 'bg-amber-950/40 text-amber-400 border-amber-800/50' : ''}
      {feedbackStatus === 'info' ? 'bg-indigo-950/40 text-indigo-300 border-indigo-900/50' : ''}">
      {feedbackMsg}
    </div>

  </div>
</div>
