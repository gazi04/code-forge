<script>
    import { onMount, untrack } from 'svelte';
    import BlockHeader from '@/components/Blocks/BlockHeader.svelte';
    import { claimMicroReward } from '@/lib/utils';

    let { data, index, lessonSlug, isAlreadyCleared = false } = $props();
    let claimedRewards = $state(null);

    let processedLines = $state([]);
    let activeLineIdx = $state(null);
    let isCleared = $state(untrack(() => isAlreadyCleared));
    let isVerifying = $state(false);
    let feedbackMsg = $state(
        untrack(() => isAlreadyCleared)
            ? '✨ Codebase verified. Hotfixes are fully integrated.'
            : 'Inspect the codebase thoroughly.',
    );
    let feedbackStatus = $state(
        untrack(() => isAlreadyCleared) ? 'success' : 'info',
    );
    let isCorrect = $derived(isCleared);

    // Buggy lines the student still has to pick a patch for. Correctness is
    // decided server-side — the correct text is never sent to the client.
    let pendingBuggyLines = $derived(
        processedLines.filter((l) => l.type === 'buggy' && !l.selected).length,
    );

    onMount(() => {
        initializeChallenge();
    });

    function initializeChallenge() {
        processedLines = data.code_lines.map((line, idx) => {
            let isBuggy = line.type === 'buggy';

            return {
                id: idx,
                type: line.type,
                initialText: line.displayed_text,
                // Server provides the pre-shuffled choices for buggy lines.
                currentText: line.displayed_text,
                choices: isBuggy ? (line.choices ?? []) : [],
                selected: false,
                fixed: isAlreadyCleared,
            };
        });

        activeLineIdx = null;
        isCleared = isAlreadyCleared;
        feedbackMsg = isAlreadyCleared
            ? '✨ Codebase verified. Hotfixes are fully integrated.'
            : 'Inspect the codebase thoroughly. Click any line to analyze its state.';
        feedbackStatus = isAlreadyCleared ? 'success' : 'info';
    }

    function handleLineClick(idx) {
        if (isCleared || isVerifying) {
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
            } else {
                feedbackMsg = `🔍 Line ${idx + 1} seems corrupted. Select an automated hotfix payload below!`;
                feedbackStatus = 'warning';
            }
        }
    }

    function applyHotfix(lineIdx, selectedOption) {
        let line = processedLines[lineIdx];
        line.currentText = selectedOption;
        line.selected = true;

        activeLineIdx = null; // Close option tray

        if (pendingBuggyLines === 0) {
            feedbackMsg =
                'All anomalies patched. Run integrity verification to confirm.';
            feedbackStatus = 'info';
        } else {
            feedbackMsg = `Patch staged. Lines still awaiting a fix: ${pendingBuggyLines}.`;
            feedbackStatus = 'info';
        }
    }

    function verifyPatches() {
        if (isCleared || isVerifying) {
            return;
        }

        isVerifying = true;
        feedbackMsg = 'Running integrity verification…';
        feedbackStatus = 'info';

        // Send each buggy line's chosen replacement, keyed by line index.
        const answer = {};
        processedLines.forEach((line) => {
            if (line.type === 'buggy') {
                answer[line.id] = line.currentText;
            }
        });

        claimMicroReward(lessonSlug, index, answer, {
            onCorrect: (rewards) => {
                isVerifying = false;
                isCleared = true;
                processedLines.forEach((line) => {
                    line.fixed = true;
                });
                feedbackMsg =
                    '🎉 Integrity Restored! All hidden compilation anomalies have been purged successfully.';
                feedbackStatus = 'success';

                if (rewards.xp > 0) {
                    claimedRewards = rewards;
                }
            },
            onIncorrect: () => {
                isVerifying = false;
                feedbackMsg =
                    'Verification failed. One or more patches are incorrect—review the lines and try again.';
                feedbackStatus = 'warning';
            },
        });
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

    <div class="p-4 sm:p-6 w-full">
        <div class="flex justify-between items-center mb-4 text-xs font-mono">
            <div
                class="px-3 py-1.5 bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] rounded-md"
            >
                Patches Pending: <span
                    class="font-bold text-[var(--text-color)]"
                    >{isCleared ? '0' : pendingBuggyLines}</span
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
                    line.type === 'buggy' && !line.selected}

                <div
                    role="button"
                    tabindex="0"
                    onclick={() => handleLineClick(idx)}
                    onkeydown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            handleLineClick(idx);
                        }
                    }}
                    class="group w-full flex items-start cursor-pointer transition-colors border-l-2 py-0.5
            {isLineActive
                        ? 'bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] border-[var(--primary-color)]'
                        : 'border-transparent hover:bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)] active:bg-[color-mix(in_srgb,var(--primary-color)_5%,transparent)]'}"
                >
                    <div
                        class="w-8 pr-2 sm:w-10 sm:pr-4 select-none text-right text-[color-mix(in_srgb,var(--bg-color)_50%,black)] group-hover:text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] transition-colors font-bold text-xs pt-0.5"
                    >
                        {idx + 1}
                    </div>

                    <div
                        class="flex-1 min-w-0 whitespace-pre-wrap break-words tracking-wide transition-colors
            {line.type === 'buggy' && line.fixed
                            ? 'text-emerald-400'
                            : isLineBuggyAndUnresolved
                              ? 'text-[var(--text-color)] group-hover:text-[var(--primary-color)]'
                              : 'text-[color-mix(in_srgb,var(--text-color)_50%,transparent)]'}"
                    >
                        {line.currentText}
                    </div>

                    <div class="px-3 text-xs select-none">
                        {#if line.type === 'buggy' && line.fixed}
                            <span class="text-emerald-500/80">✨ Patched</span>
                        {:else if line.type === 'buggy' && line.selected}
                            <span class="text-amber-400 font-bold"
                                >◌ Staged</span
                            >
                        {/if}
                    </div>
                </div>

                {#if isLineActive && line.type === 'buggy' && !line.fixed}
                    <div
                        class="w-full pl-10 pr-4 py-3 bg-[color-mix(in_srgb,var(--bg-color)_60%,transparent)] border-y border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex flex-col gap-2 my-1 animate-fadeIn"
                    >
                        <span
                            class="text-[10px] text-[var(--primary-color)] uppercase tracking-widest font-bold block mb-1"
                            >Select Patch Modification:</span
                        >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            {#each line.choices as option (option)}
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

        {#if !isCleared}
            <button
                onclick={verifyPatches}
                disabled={isVerifying || pendingBuggyLines > 0}
                class="w-full mt-4 px-8 py-3 rounded-xl font-bold uppercase tracking-wider text-sm transition-transform disabled:opacity-50 disabled:cursor-not-allowed hover:scale-[1.01] active:scale-95 bg-[var(--primary-color)] text-[var(--bg-color)]"
            >
                {isVerifying ? 'Verifying…' : 'Verify Patches'}
            </button>
        {/if}

        {#if claimedRewards}
            <div
                class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-500/10 border border-amber-500/30 rounded-lg text-xs font-bold text-amber-400"
            >
                ✨ +{claimedRewards.xp} XP & +{claimedRewards.coins} Coins Secured!
            </div>
        {/if}
    </div>
</div>
