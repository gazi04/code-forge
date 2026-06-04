<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BlockHeader from '@/components/Blocks/BlockHeader.svelte';
    import { claimMicroReward } from '@/lib/utils';

    let { data, index, lessonSlug, isAlreadyCleared = false } = $props();
    let claimedRewards = $state(null);

    let processedLines = $state([]);
    let activeLineIdx = $state(null);
    let bugsRemaining = $state(
        isAlreadyCleared
            ? 0
            : data.code_lines.filter((l) => l.type === 'buggy').length,
    );
    let isCleared = $state(isAlreadyCleared);
    let feedbackMsg = $state(
        isAlreadyCleared
            ? '✨ Codebase verified. Hotfixes are fully integrated.'
            : 'Inspect the codebase thoroughly.',
    );
    let feedbackStatus = $state(isAlreadyCleared ? 'success' : 'info');
    let isCorrect = $derived(isCleared);

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
                let choices = [
                    line.displayed_text,
                    line.correct_text,
                    line.decoy_1,
                    line.decoy_2,
                ];

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
                currentText:
                    isAlreadyCleared && isBuggy
                        ? line.correct_text
                        : line.displayed_text,
                choices: options,
                isFixed: isAlreadyCleared ? true : !isBuggy,
            };
        });

        bugsRemaining = internalBugs;
        activeLineIdx = null;
        isCleared = false;
        feedbackMsg =
            'Inspect the codebase thoroughly. Click any line to analyze its state.';
        feedbackStatus = 'info';
    }

    function handleLineClick(idx) {
        if (isCleared) {
            return;
        }

        // Toggle selector drawer open/closed
        if (activeLineIdx === idx) {
            activeLineIdx = null;
        } else {
            activeLineIdx = idx;
            const targetLine = processedLines[idx];

            if (targetLine.type === 'clean') {
                feedbackMsg = `⚡ Line ${idx + 1} looks clean! No syntax abnormalities detected here.`;
                feedbackStatus = 'info';
            } else if (targetLine.isFixed) {
                feedbackMsg = `✅ Line ${idx + 1} has already been patched and resolved.`;
                feedbackStatus = 'success';
            } else {
                feedbackMsg = `🔍 Line ${idx + 1} seems corrupted. Select an automated hotfix payload below!`;
                feedbackStatus = 'warning';
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
            feedbackMsg =
                '🎉 Integrity Restored! All hidden compilation anomalies have been purged successfully.';
            feedbackStatus = 'success';
            claimMicroReward(lessonSlug, index, (rewards) => {
                claimedRewards = rewards;
            });
        } else {
            feedbackMsg = `Patch deployed. Remaining runtime exceptions tracking count: ${bugsRemaining}.`;
            feedbackStatus = 'info';
        }
    }
</script>

<div
    class="w-full bg-[var(--bg-color)] rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl mt-8 overflow-hidden font-sans"
>
    <BlockHeader
        icon={data.game_icon || '🐛'}
        title={data.game_title || 'Bug Hunt'}
        instructions={data.instructions || 'Identify and patch the anomalies.'}
        isRequired={data.is_required}
        {isCorrect}
        xpReward={data.xp_reward}
        coinReward={data.coin_reward}
    />

    <div class="p-6 w-full">
        <div class="flex justify-between items-center mb-4 text-xs font-mono">
            <div
                class="px-3 py-1.5 bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] rounded-md"
            >
                Anomalies Found: <span class="font-bold text-[var(--text-color)]"
                    >{isCleared ? '0' : bugsRemaining}</span
                >
            </div>

            <button
                onclick={initializeChallenge}
                disabled={isCleared}
                class="text-[10px] uppercase tracking-widest font-bold text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:text-[color-mix(in_srgb,var(--text-color)_70%,transparent)] transition-colors disabled:opacity-30"
            >
                ↺ Reload Original Sandbox
            </button>
        </div>

        <div
            class="w-full bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] rounded-xl border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] shadow-inner p-4 overflow-hidden flex flex-col font-mono text-sm leading-relaxed"
        >
            {#each processedLines as line, idx (line.id)}
                {@const isLineActive = activeLineIdx === idx}
                {@const isLineBuggyAndUnresolved =
                    line.type === 'buggy' && !line.isFixed}

                <div
                    onclick={() => handleLineClick(idx)}
                    class="group w-full flex items-start cursor-pointer transition-colors border-l-2 py-0.5
            {isLineActive
                        ? 'bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border-[var(--primary-color)]'
                        : 'border-transparent hover:bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)]'}"
                >
                    <div
                        class="w-10 select-none text-right pr-4 text-[color-mix(in_srgb,var(--bg-color)_50%,black)] group-hover:text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] transition-colors font-bold text-xs pt-0.5"
                    >
                        {idx + 1}
                    </div>

                    <div
                        class="flex-1 whitespace-pre-wrap tracking-wide transition-colors
            {line.type === 'buggy' && line.isFixed
                            ? 'text-emerald-400'
                            : isLineBuggyAndUnresolved
                              ? 'text-[var(--text-color)] group-hover:text-[var(--primary-color)]'
                              : 'text-[color-mix(in_srgb,var(--text-color)_50%,transparent)]'}"
                    >
                        {line.currentText}
                    </div>

                    <div class="px-3 text-xs select-none">
                        {#if line.type === 'buggy' && line.isFixed}
                            <span class="text-emerald-500/80">✨ Patched</span>
                        {:else if isLineBuggyAndUnresolved && line.currentText !== line.initialText}
                            <span class="text-rose-400 font-bold animate-pulse"
                                >⚠️ Warning</span
                            >
                        {/if}
                    </div>
                </div>

                {#if isLineActive && line.type === 'buggy' && !line.isFixed}
                    <div
                        class="w-full pl-10 pr-4 py-3 bg-[color-mix(in_srgb,var(--bg-color)_60%,transparent)] border-y border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex flex-col gap-2 my-1 animate-fadeIn"
                    >
                        <span
                            class="text-[10px] text-[var(--primary-color)] uppercase tracking-widest font-bold block mb-1"
                            >Select Patch Modification:</span
                        >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            {#each line.choices as option}
                                <button
                                    onclick={() => applyHotfix(idx, option)}
                                    class="w-full text-left p-2.5 rounded-lg border text-xs transition-all bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] font-mono text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_60%,transparent)] hover:bg-[var(--surface-color)]
                    {line.currentText === option
                                        ? 'border-[var(--primary-color)] bg-[var(--surface-color)] text-[color-mix(in_srgb,var(--primary-color)_80%,white)]'
                                        : ''}"
                                >
                                    {option}
                                </button>
                            {/each}
                        </div>
                    </div>
                {/if}
            {/each}
        </div>

        <div
            class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center border transition-all duration-300
      {feedbackStatus === 'success'
                ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800/50'
                : ''}
      {feedbackStatus === 'warning'
                ? 'bg-amber-950/40 text-amber-400 border-amber-800/50'
                : ''}
      {feedbackStatus === 'info'
                ? 'bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)]'
                : ''}"
        >
            {feedbackMsg}
        </div>
    </div>
</div>
