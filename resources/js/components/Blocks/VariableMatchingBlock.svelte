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
        matchedIds = [];
        movesCount = 0;
        isCleared = false;
        networkFeedback =
            'Select a node from the left matrix, then find its matching anchor on the right.';
        feedbackStatus = 'info';
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
    class="w-full bg-[#0d071d] rounded-2xl border border-indigo-900/50 shadow-2xl mt-8 overflow-hidden font-sans"
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
                class="px-3 py-1.5 bg-[#150b2e] border border-indigo-900/50 text-indigo-300 rounded-md"
            >
                Bonds Connected: <span class="font-bold text-white"
                    >{matchedIds.length} / {totalPairsCount}</span
                >
            </div>

            <button
                onclick={initializeMatrix}
                disabled={isCleared}
                class="text-[10px] uppercase tracking-widest font-bold text-indigo-300/70 hover:text-indigo-200 transition-colors disabled:opacity-30"
            >
                ↺ Desynchronize Matrix
            </button>
        </div>

        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-8 relative w-full bg-[#0a0515] p-6 rounded-xl border border-indigo-950/80 shadow-inner"
        >
            <div
                class="hidden md:block absolute top-6 bottom-6 left-1/2 -translate-x-1/2 w-px bg-indigo-950/40 border-dashed border-l border-indigo-900/20"
            ></div>

            <div class="flex flex-col gap-3">
                <span
                    class="text-[10px] text-indigo-500 font-mono uppercase tracking-widest font-bold block mb-1 px-1"
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
                              ? 'bg-[#1e1145] border-purple-500 text-purple-200 shadow-[0_0_15px_rgba(168,85,247,0.3)] scale-[1.01]'
                              : 'bg-[#110924] border-indigo-950 text-indigo-200 hover:border-indigo-700/50 hover:bg-[#150b2e]'}"
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
                                    class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-ping"
                                ></span>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>

            <div class="flex flex-col gap-3">
                <span
                    class="text-[10px] text-indigo-500 font-mono uppercase tracking-widest font-bold block mb-1 px-1"
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
                              ? 'bg-[#110924] border-indigo-800 text-indigo-100 hover:border-purple-400 hover:bg-[#180d33] cursor-pointer hover:shadow-[0_0_10px_rgba(147,51,234,0.15)]'
                              : 'bg-[#110924] border-indigo-950 text-indigo-300/40 opacity-40 cursor-not-allowed'}"
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
                ? 'bg-indigo-950/40 text-indigo-300 border-indigo-900/50'
                : ''}"
        >
            {networkFeedback}
        </div>
    </div>
</div>
