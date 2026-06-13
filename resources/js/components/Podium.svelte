<script>
    import { fly } from 'svelte/transition';

    let { leaders = [], playerName = '' } = $props();

    const medals = [
        {
            label: 'Gold',
            border: 'border-yellow-400',
            glow: 'shadow-[0_0_60px_rgba(250,204,21,0.5),0_0_120px_rgba(250,204,21,0.15)]',
            text: 'text-yellow-400',
            bg: 'bg-yellow-400/10',
            platformBg: 'bg-yellow-400/15',
            platformBorder: 'border-yellow-400/60',
            platformHeight: 'h-20',
            size: 'w-20 h-20 text-2xl sm:w-24 sm:h-24 sm:text-3xl',
            order: 'order-2',
            flyDelay: 0,
        },
        {
            label: 'Silver',
            border: 'border-zinc-400',
            glow: 'shadow-[0_0_25px_rgba(161,161,170,0.3)]',
            text: 'text-zinc-400',
            bg: 'bg-zinc-400/10',
            platformBg: 'bg-zinc-400/15',
            platformBorder: 'border-zinc-400/50',
            platformHeight: 'h-14',
            size: 'w-16 h-16 text-2xl',
            order: 'order-1',
            flyDelay: 100,
        },
        {
            label: 'Bronze',
            border: 'border-amber-600',
            glow: 'shadow-[0_0_25px_rgba(180,83,9,0.3)]',
            text: 'text-amber-600',
            bg: 'bg-amber-600/10',
            platformBg: 'bg-amber-600/15',
            platformBorder: 'border-amber-600/50',
            platformHeight: 'h-8',
            size: 'w-14 h-14 text-xl',
            order: 'order-3',
            flyDelay: 200,
        },
    ];
</script>

<div class="flex justify-center items-end gap-3 sm:gap-6 mb-10">
    {#each leaders.slice(0, 3) as leader, i}
        {@const medal = medals[i]}
        {@const isPlayer = leader.name === playerName}
        <div
            class="flex flex-col items-center {medal.order}"
            in:fly={{ y: 40, duration: 500, delay: medal.flyDelay }}
        >
            {#if i === 0}
                <div class="text-2xl mb-1 animate-bounce">👑</div>
            {:else}
                <div class="mb-1 h-8"></div>
            {/if}
            <div class="{medal.size} relative">
                {#if leader.equipped?.avatar?.image_url}
                    <div
                        class="absolute inset-0 rounded-full border-2 {medal.border} {medal.glow} overflow-hidden {i === 0 ? 'pulse-ring' : ''}"
                    >
                        <img
                            src={leader.equipped.avatar.image_url}
                            alt={leader.name}
                            class="w-full h-full object-cover"
                        />
                    </div>
                {:else}
                    <div
                        class="absolute inset-0 rounded-full {medal.bg} border-2 {medal.border} {medal.glow} flex items-center justify-center font-black {medal.text} uppercase tracking-wider {i === 0 ? 'pulse-ring' : ''}"
                    >
                        {leader.name.slice(0, 2).toUpperCase()}
                    </div>
                {/if}
            </div>

            <div class="text-center mt-2 mb-2">
                <p class="font-bold text-[var(--text-color)] text-sm truncate max-w-[100px]">{leader.name}</p>
                {#if leader.equipped?.title}
                    <p class="text-[10px] font-mono" style="color: {leader.equipped.title.color ?? 'inherit'}">{leader.equipped.title.name}</p>
                {/if}
                <p class="text-xs {medal.text} font-mono font-bold">Lv. {leader.level}</p>
                <p class="text-xs text-[color-mix(in_srgb,var(--text-color)_50%,transparent)] font-mono">{leader.xp.toLocaleString()} XP</p>
                {#if isPlayer}
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-[color-mix(in_srgb,var(--primary-color)_20%,transparent)] border border-[color-mix(in_srgb,var(--primary-color)_50%,transparent)] text-[var(--primary-color)] text-[10px] font-black uppercase tracking-widest">
                        You
                    </span>
                {/if}
            </div>

            <div
                class="px-3 py-0.5 rounded-full {medal.bg} border {medal.border} {medal.text} text-xs font-black uppercase tracking-widest mb-3"
            >
                #{leader.rank}
            </div>

            <div class="w-20 sm:w-24 {medal.platformHeight} {medal.platformBg} border-t-2 border-x {medal.platformBorder} rounded-t-md"></div>
        </div>
    {/each}
</div>

<style>
    .pulse-ring::before {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2px solid rgba(250, 204, 21, 0.5);
        animation: pulse-ring 2s ease-in-out infinite;
    }

    @keyframes pulse-ring {
        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.2);
            opacity: 0;
        }
    }
</style>
