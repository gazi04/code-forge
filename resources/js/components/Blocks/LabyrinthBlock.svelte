<script>
    import { router } from '@inertiajs/svelte';
    import BlockHeader from '@/components/Blocks/BlockHeader.svelte';
    import { claimMicroReward } from '@/lib/utils';

    let { data, index, lessonSlug, isAlreadyCleared = false } = $props();
    let claimedRewards = $state(null);

    let statusMessage = $state(
        isAlreadyCleared
            ? '✨ Sector cleared! Your instruction routine is fully optimized.'
            : 'Build your instructions queue.',
    );
    let statusType = $state(isAlreadyCleared ? 'success' : 'info');
    let levelCleared = $state(isAlreadyCleared);
    let isCorrect = $derived(levelCleared);

    const rawRows = data.map_layout.trim().split('\n');
    const grid = rawRows.map((row) => row.trim().split(/\s+/));
    const height = grid.length;
    const width = grid[0]?.length || 0;

    let startX = 0,
        startY = 0;
    for (let r = 0; r < height; r++) {
        for (let c = 0; c < width; c++) {
            if (grid[r][c] === 'S') {
                startX = c;
                startY = r;
            }
        }
    }

    let playerX = $state(startX);
    let playerY = $state(startY);
    let playerDir = $state('RIGHT');
    let commandQueue = $state([]);
    let isExecuting = $state(false);
    let activeCommandIndex = $state(-1);

    const directions = ['UP', 'RIGHT', 'DOWN', 'LEFT'];

    function addCommand(type) {
        if (levelCleared || isExecuting) {
            return;
        }

        if (data.max_commands && commandQueue.length >= data.max_commands) {
            statusMessage = `⚠️ Limit reached! Max ${data.max_commands} commands.`;
            statusType = 'error';

            return;
        }

        commandQueue = [...commandQueue, type];
    }

    function removeCommand(idx) {
        if (isExecuting || levelCleared) {
            return;
        }

        commandQueue = commandQueue.filter((_, i) => i !== idx);
    }

    function clearQueue() {
        if (isExecuting || levelCleared) {
            return;
        }

        commandQueue = [];
        resetSimulation();
    }

    function resetSimulation() {
        playerX = startX;
        playerY = startY;
        playerDir = 'RIGHT';
        isExecuting = false;
        activeCommandIndex = -1;

        if (!levelCleared) {
            statusMessage =
                'Build your instructions queue and execute the routine!';
            statusType = 'info';
        }
    }

    async function runProgram() {
        if (commandQueue.length === 0 || isExecuting) {
            return;
        }

        isExecuting = true;
        playerX = startX;
        playerY = startY;
        playerDir = 'RIGHT';

        for (let i = 0; i < commandQueue.length; i++) {
            activeCommandIndex = i;
            const cmd = commandQueue[i];

            await new Promise((resolve) => setTimeout(resolve, 450));

            if (cmd === 'FORWARD') {
                let nextX = playerX;
                let nextY = playerY;

                if (playerDir === 'UP') {
                    nextY--;
                }

                if (playerDir === 'RIGHT') {
                    nextX++;
                }

                if (playerDir === 'DOWN') {
                    nextY++;
                }

                if (playerDir === 'LEFT') {
                    nextX--;
                }

                if (
                    nextX < 0 ||
                    nextX >= width ||
                    nextY < 0 ||
                    nextY >= height
                ) {
                    statusMessage = '💥 Crash! You hit the magical barrier.';
                    statusType = 'error';
                    isExecuting = false;

                    return;
                }

                if (grid[nextY][nextX] === '#') {
                    statusMessage =
                        '🪨 Thud! You marched into an obsidian wall.';
                    statusType = 'error';
                    isExecuting = false;

                    return;
                }

                playerX = nextX;
                playerY = nextY;
            } else if (cmd === 'TURN_LEFT') {
                let currIdx = directions.indexOf(playerDir);
                playerDir = directions[(currIdx - 1 + 4) % 4];
            } else if (cmd === 'TURN_RIGHT') {
                let currIdx = directions.indexOf(playerDir);
                playerDir = directions[(currIdx + 1) % 4];
            }

            if (grid[playerY][playerX] === 'E') {
                levelCleared = true;
                isExecuting = false;
                statusMessage =
                    '🎉 Quest Completed! You navigated the labyrinth!';
                statusType = 'success';
                claimMicroReward(lessonSlug, index, (rewards) => {
                    claimedRewards = rewards;
                });

                return;
            }
        }

        isExecuting = false;
        activeCommandIndex = -1;

        if (grid[playerY][playerX] !== 'E') {
            statusMessage =
                '🛑 Route terminated. You stopped short of the goal.';
            statusType = 'error';
        }
    }

    const dirRotation = {
        UP: '-90deg',
        RIGHT: '0deg',
        DOWN: '90deg',
        LEFT: '180deg',
    };
</script>

<div
    class="w-full bg-[var(--bg-color)] rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-2xl mt-8 overflow-hidden font-sans"
>
    <BlockHeader
        icon={data.game_icon || '🏃'}
        title={data.game_title || 'Labyrinth Navigation'}
        instructions={data.instructions ||
            'Build your instructions queue and execute.'}
        isRequired={data.is_required}
        {isCorrect}
        xpReward={data.xp_reward}
        coinReward={data.coin_reward}
    />

    <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 w-full">
        <div class="lg:col-span-5 flex flex-col w-full">
            <div
                class="flex-1 flex items-center justify-center bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] rounded-xl p-6 border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] shadow-inner overflow-x-auto"
            >
                <div
                    style="display: grid; grid-template-columns: repeat({width}, minmax(1.75rem, 3rem)); gap: 0.35rem;"
                    class="mx-auto w-full max-w-fit"
                >
                    {#each grid as row, r}
                        {#each row as cell, c}
                            {@const isPlayer = playerX === c && playerY === r}
                            <div
                                class="w-full aspect-square min-w-7 rounded-md flex items-center justify-center text-base sm:text-xl select-none relative transition-colors duration-300
                                {cell === '#'
                                    ? 'bg-[color-mix(in_srgb,var(--surface-color)_80%,black)] border border-[color-mix(in_srgb,var(--text-color)_15%,transparent)]'
                                    : 'bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)]'}"
                            >
                                {#if cell === '#'}
                                    <span class="opacity-40 text-sm">🧱</span>
                                {:else if cell === 'E'}
                                    <span
                                        class="drop-shadow-[0_0_8px_rgba(250,204,21,0.6)]"
                                        >🏆</span
                                    >
                                {:else if cell === 'S' && !isPlayer}
                                    <span class="opacity-30">🏰</span>
                                {/if}

                                {#if isPlayer}
                                    <div
                                        class="absolute inset-0 flex items-center justify-center transition-transform duration-200 z-10 drop-shadow-[0_0_10px_color-mix(in_srgb,var(--primary-color)_80%,transparent)]"
                                        style="transform: rotate({dirRotation[
                                            playerDir
                                        ]});"
                                    >
                                        ⚔️
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    {/each}
                </div>
            </div>

            <div
                class="w-full mt-4 p-3 rounded-lg text-xs font-mono text-center
                {statusType === 'success'
                    ? 'bg-emerald-950/40 text-emerald-400 border border-emerald-800/50'
                    : ''}
                {statusType === 'error'
                    ? 'bg-rose-950/40 text-rose-400 border border-rose-800/50'
                    : ''}
                {statusType === 'info'
                    ? 'bg-[color-mix(in_srgb,var(--primary-color)_8%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_20%,transparent)]'
                    : ''}"
            >
                {statusMessage}
            </div>
        </div>

        <div class="lg:col-span-7 flex flex-col justify-between w-full">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <span
                        class="text-[10px] text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] uppercase font-bold tracking-widest font-mono"
                        >1. Available Commands</span
                    >
                    {#if data.max_commands}
                        <span
                            class="text-[10px] font-mono px-2 py-1 bg-[var(--surface-color)] border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] rounded-md"
                            >Memory: {commandQueue.length}/{data.max_commands}</span
                        >
                    {/if}
                </div>

                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <button
                        onclick={() => addCommand('FORWARD')}
                        disabled={isExecuting || levelCleared}
                        class="p-3 bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] hover:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:scale-[0.98] disabled:opacity-30 disabled:hover:bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] rounded-xl text-xs text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] font-medium flex flex-col items-center gap-2 border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_50%,transparent)] transition-all"
                    >
                        <span class="text-xl">🏃</span> Move Fwd
                    </button>
                    <button
                        onclick={() => addCommand('TURN_LEFT')}
                        disabled={isExecuting || levelCleared}
                        class="p-3 bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] hover:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:scale-[0.98] disabled:opacity-30 disabled:hover:bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] rounded-xl text-xs text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] font-medium flex flex-col items-center gap-2 border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_50%,transparent)] transition-all"
                    >
                        <span class="text-xl">↩️</span> Turn Left
                    </button>
                    <button
                        onclick={() => addCommand('TURN_RIGHT')}
                        disabled={isExecuting || levelCleared}
                        class="p-3 bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] hover:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:bg-[color-mix(in_srgb,var(--primary-color)_15%,var(--bg-color))] active:scale-[0.98] disabled:opacity-30 disabled:hover:bg-[color-mix(in_srgb,var(--bg-color)_90%,black)] rounded-xl text-xs text-[color-mix(in_srgb,var(--text-color)_80%,transparent)] font-medium flex flex-col items-center gap-2 border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[color-mix(in_srgb,var(--primary-color)_50%,transparent)] transition-all"
                    >
                        <span class="text-xl">↪️</span> Turn Right
                    </button>
                </div>

                <div class="mt-8">
                    <span
                        class="text-[10px] text-[color-mix(in_srgb,var(--text-color)_45%,transparent)] uppercase font-bold tracking-widest font-mono block mb-3"
                        >2. Execution Stack</span
                    >
                    <div
                        class="bg-[color-mix(in_srgb,var(--bg-color)_80%,black)] border border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] rounded-xl p-4 min-h-[120px] flex flex-wrap gap-2 items-start content-start shadow-inner"
                    >
                        {#if commandQueue.length === 0}
                            <p
                                class="text-xs text-[color-mix(in_srgb,var(--text-color)_20%,transparent)] font-mono w-full text-center mt-6 pointer-events-none select-none"
                            >
                                Stack is empty. Awaiting instructions...
                            </p>
                        {/if}

                        {#each commandQueue as cmd, idx}
                            {@const isActive = activeCommandIndex === idx}
                            <div
                                onclick={() => removeCommand(idx)}
                                class="px-3 py-2 bg-[var(--surface-color)] hover:bg-rose-950/40 hover:border-rose-800/60 hover:text-rose-300 border text-xs font-mono rounded-lg flex items-center gap-2 cursor-pointer transition-all
                                {isActive
                                    ? 'bg-[var(--primary-color)] text-[var(--bg-color)] border-[color-mix(in_srgb,var(--primary-color)_70%,transparent)] shadow-[0_0_15px_color-mix(in_srgb,var(--primary-color)_50%,transparent)] scale-105'
                                    : 'border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] text-[color-mix(in_srgb,var(--text-color)_80%,transparent)]'}"
                            >
                                <span class="opacity-50 text-[10px]"
                                    >{idx + 1}:</span
                                >
                                {cmd === 'FORWARD'
                                    ? '🏃 FWD'
                                    : cmd === 'TURN_LEFT'
                                      ? '↩️ LFT'
                                      : '↪️ RGT'}
                            </div>
                        {/each}
                    </div>
                </div>
            </div>

            <div
                class="mt-6 pt-5 border-t border-[color-mix(in_srgb,var(--text-color)_8%,transparent)] flex gap-4"
            >
                <button
                    onclick={runProgram}
                    disabled={commandQueue.length === 0 ||
                        isExecuting ||
                        levelCleared}
                    class="flex-1 py-3.5 rounded-xl font-bold uppercase tracking-widest text-[11px] transition-all
                    {levelCleared
                        ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30'
                        : 'bg-[var(--primary-color)] text-[var(--bg-color)] hover:bg-[color-mix(in_srgb,var(--primary-color)_80%,white)] hover:shadow-[0_0_20px_color-mix(in_srgb,var(--primary-color)_40%,transparent)]'}
                    disabled:opacity-40 disabled:hover:bg-[var(--primary-color)] disabled:hover:shadow-none"
                >
                    {isExecuting
                        ? '⚡ Compiling...'
                        : levelCleared
                          ? '✨ Mastered'
                          : '▶️ Execute Stack'}
                </button>

                <button
                    onclick={clearQueue}
                    disabled={isExecuting || levelCleared}
                    class="px-6 py-3.5 bg-transparent border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:bg-[var(--surface-color)] hover:border-[color-mix(in_srgb,var(--primary-color)_50%,transparent)] text-[color-mix(in_srgb,var(--text-color)_60%,transparent)] text-[11px] font-bold rounded-xl disabled:opacity-30 uppercase tracking-widest transition-all"
                >
                    Clear
                </button>
            </div>
        </div>
    </div>
</div>
