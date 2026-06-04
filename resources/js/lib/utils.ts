import type { LinkComponentBaseProps } from '@inertiajs/core';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { router } from '@inertiajs/svelte';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(
    href: NonNullable<LinkComponentBaseProps['href']>,
): string {
    return typeof href === 'string' ? href : href.url;
}

export function claimMicroReward(
    lessonSlug: string,
    blockIndex: number,
    onRewardClaimed: (rewards: { xp: number; coins: number }) => void
) {
    router.post(`/lessons/${lessonSlug}/blocks/${blockIndex}/claim`, {}, {
        preserveScroll: true,
        onSuccess: (page: any) => {
            const res = page.props.flash?.game_result;
            if (res && res.status !== 'already_completed') {
                // Pass the data back to the component to trigger its local animations
                onRewardClaimed({
                    xp: res.total_xp_earned || 15,
                    coins: res.coins_earned || 5
                });
            }
        }
    });
}
