<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';

    let { data, index, lessonSlug } = $props();
    let claimedRewards = $state(null);

    // Extract values from filament schema structure
    const correctOrder = data.correct_sequence.map((item) => item.value);

    // State Management via Svelte 5 Runes
    let dynamicList = $state([]);
    let selectedIndex = $state(null);
    let attemptsCount = $state(0);
    let isCleared = $state(false);
    let gameFeedback = $state(
        'Click an item to select it, then click another to swap their positions.',
    );
    let feedbackStatus = $state('info'); // info, success, error
    let isCorrect = $derived(isCleared);

    // Deterministic/Random shuffle execution upon mount sequence
    onMount(() => {
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

    function claimMicroReward() {
        router.post(`/lessons/${lessonSlug}/blocks/${index}/claim`, {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const res = page.props.flash?.game_result;
                if (res && res.status !== 'already_completed') {
                    claimedRewards = {
                        xp: res.total_xp_earned || 15,
                        coins: res.coins_earned || 5
                    };
                }
            }
        });
    }

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
            claimMicroReward();
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
    class="w-full bg-[#0d071d] rounded-2xl border border-indigo-900/50 shadow-2xl mt-8 overflow-hidden font-sans"
>
    <div
        class="bg-[#150b2e] px-6 py-4 border-b border-indigo-900/50 flex justify-between items-center"
    >
        <div class="flex items-center gap-4">
            <div
                class="w-10 h-10 rounded-full bg-indigo-950 border border-indigo-500/30 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(99,102,241,0.2)]"
            >
                {data.game_icon || '📜'}
            </div>
            <div>
                <h4
                    class="font-serif font-bold text-indigo-100 text-lg tracking-wide"
                >
                    {data.game_title}
                </h4>
                <p class="text-xs text-indigo-300/60 font-mono mt-0.5">
                    {data.instructions}
                </p>
            </div>
        </div>

        <!-- Bonus Rewards Display -->
        {#if (data.xp_reward > 0 || data.coin_reward > 0) && !isCorrect}
            <div class="flex gap-2 text-xs font-mono opacity-70">
                {#if data.xp_reward > 0}<span>✨ +{data.xp_reward}</span>{/if}
                {#if data.coin_reward > 0}<span>💰 +{data.coin_reward}</span>{/if}
            </div>
        {:else if isCorrect && (data.xp_reward > 0 || data.coin_reward > 0)}
            <div
                class="text-xs font-mono text-[var(--primary-color)] animate-bounce"
            >
                Loot Acquired!
            </div>
        {/if}

        {#if data.is_required && !isCleared}
            <span
                class="px-3 py-1 rounded-full text-[10px] font-bold bg-purple-900/30 text-purple-300 border border-purple-700/50 uppercase tracking-widest"
            >
                Required
            </span>
        {/if}
    </div>

    <div class="p-6 w-full">
        <div class="flex justify-between items-center mb-4 text-xs font-mono">
            <div
                class="px-3 py-1.5 bg-[#150b2e] border border-indigo-900/50 text-indigo-300 rounded-md"
            >
                Swaps Made: <span class="font-bold text-white"
                    >{attemptsCount}</span
                >
            </div>

            <button
                onclick={resetSequence}
                disabled={isCleared}
                class="text-[10px] uppercase tracking-widest font-bold text-indigo-300/70 hover:text-indigo-200 transition-colors disabled:opacity-30"
            >
                ↺ Reset Order
            </button>
        </div>

        <div
            class="w-full bg-[#0a0515] rounded-xl p-4 border border-indigo-950/80 shadow-inner flex flex-col gap-2"
        >
            {#each dynamicList as elementValue, idx (elementValue + '-' + idx)}
                {@const isCurrentSelection = selectedIndex === idx}

                <div
                    onclick={() => selectItem(idx)}
                    class="w-full p-4 rounded-xl border font-mono text-sm transition-all duration-200 flex items-center gap-4 select-none cursor-pointer
            {isCleared
                        ? 'bg-emerald-950/30 border-emerald-800/80 text-emerald-300 shadow-[0_0_10px_rgba(168,85,247,0.05)]'
                        : isCurrentSelection
                          ? 'bg-[#1e1145] border-purple-500 text-purple-200 shadow-[0_0_15px_rgba(168,85,247,0.35)] scale-[1.01]'
                          : 'bg-[#110924] border-indigo-950 text-indigo-200 hover:border-indigo-700/50 hover:bg-[#150b2e]'}"
                >
                    <div
                        class="w-6 h-6 rounded-md bg-black/40 border border-indigo-900/40 flex items-center justify-center text-[11px] font-bold text-indigo-400/70"
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
                              ? 'bg-purple-500 shadow-[0_0_8px_#a855f7]'
                              : 'bg-indigo-950'}"
                    ></div>
                </div>
            {/each}
        </div>

        <div
            class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center border transition-all
      {feedbackStatus === 'success'
                ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800/50'
                : 'bg-indigo-950/40 text-indigo-300 border-indigo-900/50'}"
        >
            {gameFeedback}
        </div>
    </div>
</div>
