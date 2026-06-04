<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BlockHeader from '@/components/Blocks/BlockHeader.svelte';
    import { claimMicroReward } from '@/lib/utils';

    let { data, index, lessonSlug, isAlreadyCleared = false } = $props();
    let claimedRewards = $state(null);

    const correctOrder = data.correct_sequence.map((item) => item.value);
    let dynamicList = $state([]);
    let isCleared = $state(isAlreadyCleared);
    let gameFeedback = $state(
        isAlreadyCleared
            ? '✨ Sequence fully restored and verified.'
            : 'Click an item to select it...',
    );
    let feedbackStatus = $state(isAlreadyCleared ? 'success' : 'info');
    let isCorrect = $derived(isCleared);

    let selectedIndex = $state(null);
    let attemptsCount = $state(0);

    onMount(() => {
        if (isAlreadyCleared) {
            dynamicList = [...correctOrder];
            return;
        }

        let shuffled = [...correctOrder];

        // Keep shuffling until it doesn't match the correct answer by absolute accident
        while (
            shuffled.join('|||') === correctOrder.join('|||') &&
            shuffled.length > 1
        ) {
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
        }

        dynamicList = shuffled;
    });

    function selectItem(idx) {
        if (isCleared) {
            return;
        }

        if (selectedIndex === idx) {
            selectedIndex = null; // Toggle off if clicked twice

            return;
        }

        if (selectedIndex === null) {
            selectedIndex = idx;
        } else {
            // Universal execution: Swap ANY two positions clicked by the student
            let temp = dynamicList[selectedIndex];
            dynamicList[selectedIndex] = dynamicList[idx];
            dynamicList[idx] = temp;

            attemptsCount++;
            selectedIndex = null;
            evaluateSequence();
        }
    }

    function evaluateSequence() {
        const isCorrect = dynamicList.join('|||') === correctOrder.join('|||');

        if (isCorrect) {
            isCleared = true;
            gameFeedback = `🎉 Order Restored! You aligned the sequence perfectly in ${attemptsCount} interactions.`;
            feedbackStatus = 'success';
            claimMicroReward(lessonSlug, index, (rewards) => {
                claimedRewards = rewards;
            });
        } else {
            gameFeedback =
                'Sequence adjusted. The sequence matrix is still unstable—keep sorting!';
            feedbackStatus = 'info';
        }
    }

    function resetSequence() {
        let shuffled = [...correctOrder];

        while (
            shuffled.join('|||') === correctOrder.join('|||') &&
            shuffled.length > 1
        ) {
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
        }

        dynamicList = shuffled;
        selectedIndex = null;
        attemptsCount = 0;
        isCleared = false;
        gameFeedback =
            'Click an item to select it, then click another to swap their positions.';
        feedbackStatus = 'info';
    }
</script>

<div
    class="w-full bg-[var(--bg-color)] rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl mt-8 overflow-hidden font-sans"
>
    <BlockHeader
        icon={data.game_icon || '📜'}
        title={data.game_title || 'Sequence Alignment'}
        instructions={data.instructions ||
            'Restore the proper chronological order.'}
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
                Swaps Made: <span class="font-bold text-[var(--text-color)]"
                    >{attemptsCount}</span
                >
            </div>

            <button
                onclick={resetSequence}
                disabled={isCleared}
                class="text-[10px] uppercase tracking-widest font-bold text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] hover:text-[color-mix(in_srgb,var(--text-color)_70%,transparent)] transition-colors disabled:opacity-30"
            >
                ↺ Reset Order
            </button>
        </div>

        <div
            class="w-full bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] rounded-xl p-4 border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] shadow-inner flex flex-col gap-2"
        >
            {#each dynamicList as elementValue, idx (elementValue + '-' + idx)}
                {@const isCurrentSelection = selectedIndex === idx}

                <div
                    onclick={() => selectItem(idx)}
                    class="w-full p-4 rounded-xl border font-mono text-sm transition-all duration-200 flex items-center gap-4 select-none cursor-pointer
            {isCleared
                        ? 'bg-emerald-950/30 border-emerald-800/80 text-emerald-300 shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_5%,transparent)]'
                        : isCurrentSelection
                          ? 'bg-[color-mix(in_srgb,var(--primary-color)_20%,var(--bg-color))] border-[var(--primary-color)] text-[var(--primary-color)] shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_35%,transparent)] scale-[1.01]'
                          : 'bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_40%,transparent)] hover:bg-[var(--surface-color)]'}"
                >
                    <div
                        class="w-6 h-6 rounded-md bg-black/40 border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] flex items-center justify-center text-[11px] font-bold text-[color-mix(in_srgb,var(--text-color)_40%,transparent)]"
                    >
                        {idx + 1}
                    </div>

                    <div class="flex-1 whitespace-pre-wrap tracking-wide">
                        {elementValue}
                    </div>

                    <div
                        class="w-2 h-2 rounded-full transition-all duration-300
            {isCleared
                            ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]'
                            : isCurrentSelection
                              ? 'bg-[var(--primary-color)] shadow-[0_0_8px_var(--primary-color)]'
                              : 'bg-[color-mix(in_srgb,var(--text-color)_5%,transparent)]'}"
                    ></div>
                </div>
            {/each}
        </div>

        <div
            class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center border transition-all
      {feedbackStatus === 'success'
                ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800/50'
                : 'bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)]'}"
        >
            {gameFeedback}
        </div>
    </div>
</div>
