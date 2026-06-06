<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BlockHeader from '@/components/Blocks/BlockHeader.svelte';
    import { claimMicroReward } from '@/lib/utils';

    let { data, index, lessonSlug, isAlreadyCleared = false } = $props();
    let claimedRewards = $state(null);

    let leftNodes = $state([]);
    let rightNodes = $state([]);
    let matchedIds = $state(
        isAlreadyCleared ? data.pairs.map((_, idx) => idx) : [],
    );
    let isCleared = $state(isAlreadyCleared);
    let networkFeedback = $state(
        isAlreadyCleared
            ? '✨ All node alignments secured and stable.'
            : 'Select a node from the left matrix...',
    );
    let feedbackStatus = $state(isAlreadyCleared ? 'success' : 'info');
    let isCorrect = $derived(isCleared);

    // State Trackers via Svelte 5 Runes
    let selectedLeftId = $state(null);

    let totalPairsCount = data.pairs.length;
    let movesCount = $state(0);

    onMount(() => {
        initializeMatrix();
    });

    function initializeMatrix() {
        // Break relationships down into independent structures mapped with a common relational ID
        let leftSide = data.pairs.map((pair, idx) => ({
            id: idx,
            text: pair.left_item,
        }));
        let rightSide = data.pairs.map((pair, idx) => ({
            id: idx,
            text: pair.right_item,
        }));

        // Completely Scramble Column A
        for (let i = leftSide.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [leftSide[i], leftSide[j]] = [leftSide[j], leftSide[i]];
        }

        // Completely Scramble Column B
        for (let i = rightSide.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [rightSide[i], rightSide[j]] = [rightSide[j], rightSide[i]];
        }

        leftNodes = leftSide;
        rightNodes = rightSide;
        selectedLeftId = null;
        matchedIds = isAlreadyCleared ? data.pairs.map((_, idx) => idx) : [];
        movesCount = 0;
        isCleared = isAlreadyCleared;
        networkFeedback = isAlreadyCleared
            ? '✨ All node alignments secured and stable.'
            : 'Select a node from the left matrix, then find its matching anchor on the right.';
        feedbackStatus = isAlreadyCleared ? 'success' : 'info';
    }

    function handleLeftClick(id) {
        if (isCleared || matchedIds.includes(id)) {
            return;
        }

        // Toggle active state selection
        if (selectedLeftId === id) {
            selectedLeftId = null;
        } else {
            selectedLeftId = id;
            networkFeedback =
                'Left terminal locked. Complete the bridge circuit on the right column.';
            feedbackStatus = 'info';
        }
    }

    function handleRightClick(id) {
        if (isCleared || matchedIds.includes(id) || selectedLeftId === null) {
            return;
        }

        movesCount++;

        // Evaluate relational verification ID matching logic
        if (selectedLeftId === id) {
            matchedIds = [...matchedIds, id];
            selectedLeftId = null;

            networkFeedback = '⚡ Connection established! Core link verified.';
            feedbackStatus = 'info';

            checkWinCondition();
        } else {
            // Failed Connection attempt
            selectedLeftId = null;
            networkFeedback =
                '💥 Link Refused! Relational constraints do not match.';
            feedbackStatus = 'error';
        }
    }

    function checkWinCondition() {
        if (matchedIds.length === totalPairsCount) {
            isCleared = true;
            networkFeedback = `🎉 Matrix Synchronization Complete! All relational data layers anchored flawlessly in ${movesCount} actions.`;
            feedbackStatus = 'success';
            claimMicroReward(lessonSlug, index, (rewards) => {
                claimedRewards = rewards;
            });
        }
    }
</script>

<div
    class="w-full bg-[var(--bg-color)] rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl mt-8 overflow-hidden font-sans"
>
    <BlockHeader
        icon={data.game_icon || '🔗'}
        title={data.game_title || 'Neural Mapping'}
        instructions={data.instructions ||
            'Match the variables to their core definitions.'}
        isRequired={data.is_required}
        {isCorrect}
        xpReward={data.xp_reward}
        coinReward={data.coin_reward}
    />

    <div class="p-6 w-full">
        <div class="flex justify-between items-center mb-6 text-xs font-mono">
            <div
                class="px-3 py-1.5 bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] rounded-md"
            >
                Bonds Connected: <span class="font-bold text-[var(--text-color)]"
                    >{matchedIds.length} / {totalPairsCount}</span
                >
            </div>

            <button
                onclick={initializeMatrix}
                disabled={isCleared}
                class="text-[10px] uppercase tracking-widest font-bold text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:text-[color-mix(in_srgb,var(--text-color)_70%,transparent)] transition-colors disabled:opacity-30"
            >
                ↺ Desynchronize Matrix
            </button>
        </div>

        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-8 relative w-full bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] p-6 rounded-xl border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] shadow-inner"
        >
            <div
                class="hidden md:block absolute top-6 bottom-6 left-1/2 -translate-x-1/2 w-px bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)] border-dashed border-l border-[color-mix(in_srgb,var(--text-color)_8%,transparent)]"
            ></div>

            <div class="flex flex-col gap-3">
                <span
                    class="text-[10px] text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono uppercase tracking-widest font-bold block mb-1 px-1"
                    >Source Relic Matrices</span
                >
                {#each leftNodes as leftItem (leftItem.id)}
                    {@const isSelected = selectedLeftId === leftItem.id}
                    {@const isAlreadyMatched = matchedIds.includes(leftItem.id)}

                    <div
                        onclick={() => handleLeftClick(leftItem.id)}
                        class="p-4 rounded-xl font-mono text-xs border text-left transition-all select-none cursor-pointer
              {isAlreadyMatched
                            ? 'bg-emerald-950/20 border-emerald-900/50 text-emerald-400/60 opacity-50 cursor-default pointer-events-none'
                            : isSelected
                              ? 'bg-[color-mix(in_srgb,var(--primary-color)_20%,var(--bg-color))] border-[var(--primary-color)] text-[var(--primary-color)] shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_30%,transparent)] scale-[1.01]'
                              : 'bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] hover:bg-[var(--surface-color)]'}"
                    >
                        <div class="flex justify-between items-center">
                            <span class="tracking-wide font-bold"
                                >{leftItem.text}</span
                            >
                            {#if isAlreadyMatched}
                                <span
                                    class="text-[10px] text-emerald-500/70 font-bold"
                                    >🔒 Bound</span
                                >
                            {:else if isSelected}
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)] animate-ping"
                                ></span>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>

            <div class="flex flex-col gap-3">
                <span
                    class="text-[10px] text-[color-mix(in_srgb,var(--text-color)_40%,transparent)] font-mono uppercase tracking-widest font-bold block mb-1 px-1"
                    >Anchor Alignment Deck</span
                >
                {#each rightNodes as rightItem (rightItem.id)}
                    {@const isAlreadyMatched = matchedIds.includes(
                        rightItem.id,
                    )}
                    {@const isInteractable =
                        selectedLeftId !== null && !isAlreadyMatched}

                    <div
                        onclick={() => handleRightClick(rightItem.id)}
                        class="p-4 rounded-xl font-mono text-xs border text-left transition-all select-none
              {isAlreadyMatched
                            ? 'bg-emerald-950/20 border-emerald-900/50 text-emerald-400/60 opacity-50 pointer-events-none'
                            : isInteractable
                              ? 'bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] border-[color-mix(in_srgb,var(--primary-color)_60%,transparent)] text-[var(--text-color)] hover:border-[color-mix(in_srgb,var(--primary-color)_70%,transparent)] hover:bg-[color-mix(in_srgb,var(--primary-color)_10%,var(--bg-color))] cursor-pointer hover:shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_15%,transparent)]'
                              : 'bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-[color-mix(in_srgb,var(--text-color)_30%,transparent)] opacity-40 cursor-not-allowed'}"
                    >
                        <div class="flex justify-between items-center">
                            <span class="tracking-wide">{rightItem.text}</span>
                            {#if isAlreadyMatched}
                                <span class="text-xs text-emerald-400/80"
                                    >✓ Connected</span
                                >
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        </div>

        <div
            class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center border transition-all duration-300
      {feedbackStatus === 'success'
                ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800/50'
                : ''}
      {feedbackStatus === 'error'
                ? 'bg-rose-950/40 text-rose-400 border-rose-800/50 animate-shake'
                : ''}
      {feedbackStatus === 'info'
                ? 'bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)]'
                : ''}"
        >
            {networkFeedback}
        </div>
    </div>
</div>
