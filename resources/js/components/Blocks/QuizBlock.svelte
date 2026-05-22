<script>
  let { data, index } = $props();

  let selectedIndexes = $state([]);
  let isSubmitted = $state(false);
  let isCorrect = $state(false);
  let feedbackMessages = $state([]);

  const isMultiple = data.question_type === 'multiple';

  function toggleSelection(ansIndex) {
    // Prevent changing answers after a correct submission
    if (isCorrect) return;

    isSubmitted = false;
    feedbackMessages = [];

    if (isMultiple) {
      if (selectedIndexes.includes(ansIndex)) {
        selectedIndexes = selectedIndexes.filter(i => i !== ansIndex);
      } else {
        selectedIndexes = [...selectedIndexes, ansIndex];
      }
    } else {
      selectedIndexes = [ansIndex];
    }
  }

  function submitQuiz() {
    if (selectedIndexes.length === 0) return;

    isSubmitted = true;
    feedbackMessages = [];

    // Gather correct indexes from the database data
    const correctIndexes = data.answers
      .map((ans, idx) => ans.is_correct ? idx : null)
      .filter(idx => idx !== null);

    // Check if the arrays match exactly (regardless of order for multiple choice)
    const passed = selectedIndexes.length === correctIndexes.length &&
                   selectedIndexes.every(val => correctIndexes.includes(val));

    if (passed) {
      isCorrect = true;
      feedbackMessages.push({ text: "Correct! You may proceed.", type: 'success' });
    } else {
      isCorrect = false;
      // Show specific feedback for the selected wrong answers, if provided
      let customFeedbackFound = false;
      selectedIndexes.forEach(idx => {
        if (!data.answers[idx].is_correct && data.answers[idx].feedback) {
          feedbackMessages.push({ text: data.answers[idx].feedback, type: 'error' });
          customFeedbackFound = true;
        }
      });

      // Default fallback message
      if (!customFeedbackFound) {
        feedbackMessages.push({ text: "Wrong answer, try again.", type: 'error' });
      }
    }
  }
</script>

<div class="bg-surface rounded-2xl border border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] shadow-lg mt-8 overflow-hidden">

  <!-- Quiz Header -->
  <div class="bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)] px-6 py-4 border-b border-[color-mix(in_srgb,var(--text-color)_5%,transparent)] flex justify-between items-start sm:items-center">
    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
      <div class="flex items-center gap-2 text-[var(--primary-color)] font-bold tracking-wider uppercase text-sm">
        <span>🛡️</span> Encounter
      </div>
      {#if data.is_required}
        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-black bg-red-500/20 text-red-400 border border-red-500/30">
          Required
        </span>
      {/if}
    </div>

    <!-- Bonus Rewards Display -->
    {#if (data.xp_reward > 0 || data.coin_reward > 0) && !isCorrect}
      <div class="flex gap-2 text-xs font-mono opacity-70">
        {#if data.xp_reward > 0}<span>✨ +{data.xp_reward}</span>{/if}
        {#if data.coin_reward > 0}<span>💰 +{data.coin_reward}</span>{/if}
      </div>
    {:else if isCorrect && (data.xp_reward > 0 || data.coin_reward > 0)}
      <div class="text-xs font-mono text-[var(--primary-color)] animate-bounce">
        Loot Acquired!
      </div>
    {/if}
  </div>

  <!-- Question Body -->
  <div class="p-6">
    <h3 class="text-lg font-medium text-[var(--text-color)] mb-6 leading-relaxed">
      {data.question}
    </h3>

    <div class="space-y-3">
      {#each data.answers as answer, i}
        {@const isSelected = selectedIndexes.includes(i)}

        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div
          class="p-4 rounded-xl border transition-all cursor-pointer flex items-start gap-4
            {isSelected
              ? 'border-[var(--primary-color)] bg-[color-mix(in_srgb,var(--primary-color)_10%,transparent)]'
              : 'border-[color-mix(in_srgb,var(--text-color)_10%,transparent)] hover:border-[color-mix(in_srgb,var(--text-color)_30%,transparent)]'}
            {isCorrect ? 'opacity-60 cursor-not-allowed' : ''}"
          onclick={() => toggleSelection(i)}
        >
          <!-- Custom Checkbox/Radio UI -->
          <div class="mt-1 w-5 h-5 flex-shrink-0 border flex items-center justify-center
            {isMultiple ? 'rounded' : 'rounded-full'}
            {isSelected ? 'border-[var(--primary-color)] bg-[var(--primary-color)] text-[var(--bg-color)]' : 'border-[color-mix(in_srgb,var(--text-color)_30%,transparent)]'}">
            {#if isSelected}
              <span class="text-xs font-black">✓</span>
            {/if}
          </div>
          <span class="text-[var(--text-color)] opacity-90">{answer.text}</span>
        </div>
      {/each}
    </div>

    <!-- Action & Feedback Footer -->
    <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
      <button
        onclick={submitQuiz}
        disabled={selectedIndexes.length === 0 || isCorrect}
        class="w-full sm:w-auto px-8 py-3 rounded-xl font-bold uppercase tracking-wider text-sm transition-transform disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 hover:scale-105 active:scale-95
          {isCorrect
            ? 'bg-green-500/20 text-green-400 border border-green-500/30'
            : 'bg-[var(--primary-color)] text-[var(--bg-color)]'}"
      >
        {isCorrect ? 'Victory Achieved' : 'Submit Answer'}
      </button>

      {#if isSubmitted}
        <div class="flex flex-col gap-1">
          {#each feedbackMessages as msg}
            <span class="text-sm font-medium {msg.type === 'success' ? 'text-green-400' : 'text-red-400'} animate-fade-in">
              {msg.type === 'success' ? '✨' : '❌'} {msg.text}
            </span>
          {/each}
        </div>
      {/if}
    </div>

  </div>
</div>

<style>
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
  }
</style>
