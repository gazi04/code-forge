<script>
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';

    let { data, index, lessonSlug } = $props();
    let claimedRewards = $state(null);

    let userCode = $state(data.initial_code || '');
    let terminalOutput = $state('');
    let isExecuting = $state(false);
    let pyodideReady = $state(false);
    let pyodideInstance = null;

    let testResults = $state(
        data.test_cases?.map((tc) => ({ ...tc, passed: null })) || [],
    );
    let isCorrect = $derived(testResults.length > 0 && testResults.every(t => t.passed === true));

    onMount(async () => {
        if (data.language === 'python') {
            const script = document.createElement('script');
            script.src =
                'https://cdn.jsdelivr.net/pyodide/v0.25.0/full/pyodide.js';
            script.onload = async () => {
                pyodideInstance = await window.loadPyodide();
                pyodideReady = true;
            };
            document.head.appendChild(script);
        } else {
            pyodideReady = true;
        }
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

    async function runCode() {
        isExecuting = true;
        terminalOutput = '> Initializing validation sequence...\n\n';

        if (data.language === 'python') {
            let allTestsPassed = true;

            for (let i = 0; i < testResults.length; i++) {
                const test = testResults[i];

                try {
                    pyodideInstance.runPython(`
            import sys
            import io
            sys.stdout = io.StringIO()
          `);

                    const scriptToRun = `${userCode}\n\n${test.setup_code || ''}`;

                    await pyodideInstance.runPythonAsync(scriptToRun);

                    let actualOutput = pyodideInstance
                        .runPython('sys.stdout.getvalue()')
                        .trim();

                    const expected = test.expected_output.trim();
                    const passed = actualOutput === expected;

                    testResults[i].passed = passed;

                    if (passed) {
                        terminalOutput += `✅ [${test.name}]: Passed! (Captured: "${actualOutput}")\n`;
                    } else {
                        allTestsPassed = false;
                        terminalOutput += `❌ [${test.name}]: Failed.\n   Expected: "${expected}"\n   Got: "${actualOutput}"\n`;
                    }
                } catch (err) {
                    allTestsPassed = false;
                    testResults[i].passed = false;
                    terminalOutput += `💥 [${test.name}] Runtime Error: ${err.message}\n`;
                }
            }

            if (allTestsPassed && testResults.length > 0) {
                terminalOutput +=
                    '\n✨ QUEST COMPLETE! All validations passed. ✨';
                claimMicroReward();
            } else {
                terminalOutput +=
                    '\n❌ Some objectives have failed. Adjust your scrolls and try again.';
            }
        }

        isExecuting = false;
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
                {data.game_icon || '🖥️'}
            </div>
            <div>
                <h4
                    class="font-serif font-bold text-indigo-100 text-lg tracking-wide"
                >
                    Arcane Terminal ({data.language})
                </h4>

                <p class="text-xs text-indigo-300/60 font-mono mt-0.5">
                    instructions
                </p>

                {#if data.is_required}
                    <span
                        class="px-2 py-0.5 rounded text-[10px] uppercase font-black bg-red-500/20 text-red-400 border border-red-500/30"
                    >
                        Required
                    </span>
                {/if}
            </div>
        </div>


        {#if !pyodideReady}
            <span class="text-xs font-mono text-amber-400 animate-pulse"
            >Summoning Interpreter...</span
            >
        {/if}

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

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2">
        <div
            class="relative border-r border-[color-mix(in_srgb,var(--text-color)_5%,transparent)]"
        >
            <textarea
                bind:value={userCode}
                class="w-full h-[400px] bg-transparent p-6 font-mono text-sm text-[var(--text-color)] focus:outline-none resize-none"
                spellcheck="false"
            ></textarea>

            <div class="absolute bottom-4 right-4">
                <button
                    onclick={runCode}
                    disabled={!pyodideReady || isExecuting}
                    class="px-6 py-2 rounded-lg bg-[var(--primary-color)] text-[var(--bg-color)] font-bold uppercase tracking-wider text-xs hover:scale-105 active:scale-95 transition-transform disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    {#if isExecuting}
                        Casting...
                    {:else}
                        ▶ Execute Code
                    {/if}
                </button>
            </div>
        </div>

        <div class="flex flex-col h-[400px]">
            <div class="flex-1 p-6 bg-[#0a0a0a] overflow-y-auto">
                <div
                    class="font-mono text-xs text-zinc-500 uppercase tracking-widest mb-2"
                >
                    Standard Output
                </div>
                <pre
                    class="font-mono text-sm text-green-400 whitespace-pre-wrap">{terminalOutput}</pre>
            </div>

            {#if testResults.length > 0}
                <div
                    class="h-1/2 p-6 bg-[color-mix(in_srgb,var(--bg-color)_30%,transparent)] border-t border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] overflow-y-auto"
                >
                    <div
                        class="font-mono text-xs text-zinc-500 uppercase tracking-widest mb-4"
                    >
                        Quest Objectives
                    </div>

                    <div class="space-y-3">
                        {#each testResults as test}
                            {#if !test.is_hidden}
                                <div class="flex items-center gap-3 text-sm">
                                    {#if test.passed === null}
                                        <div
                                            class="w-5 h-5 rounded-full border-2 border-zinc-700"
                                        ></div>
                                    {:else if test.passed}
                                        <div
                                            class="w-5 h-5 rounded-full bg-[var(--accent-color)] flex items-center justify-center text-[var(--bg-color)] text-xs"
                                        >
                                            ✓
                                        </div>
                                    {:else}
                                        <div
                                            class="w-5 h-5 rounded-full bg-red-500 flex items-center justify-center text-white text-xs"
                                        >
                                            ✕
                                        </div>
                                    {/if}
                                    <span
                                        class="font-mono opacity-80"
                                        class:opacity-40={test.passed === null}
                                    >{test.name}</span
                                    >
                                </div>
                            {/if}
                        {/each}
                    </div>
                </div>
            {/if}
        </div>
    </div>
</div>
