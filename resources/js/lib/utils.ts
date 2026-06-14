import type {
    FormDataConvertible,
    LinkComponentBaseProps,
} from '@inertiajs/core';
import { router } from '@inertiajs/svelte';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(
    href: NonNullable<LinkComponentBaseProps['href']>,
): string {
    return typeof href === 'string' ? href : href.url;
}

interface ClaimCallbacks {
    /** Server confirmed the answer (or it was already cleared previously). */
    onCorrect?: (rewards: { xp: number; coins: number }) => void;
    /** Server rejected the submitted answer. */
    onIncorrect?: () => void;
}

/**
 * Submit a block answer for server-side validation. The server is the only
 * authority on correctness — answer keys are stripped from the page props —
 * so the component must react to the callbacks rather than self-checking.
 */
export function claimMicroReward(
    lessonSlug: string,
    blockIndex: number,
    answer: unknown,
    callbacks: ClaimCallbacks = {},
) {
    router.post(
        `/lessons/${lessonSlug}/blocks/${blockIndex}/claim`,
        { answer: answer as FormDataConvertible },
        {
            preserveScroll: true,
            onSuccess: (page: any) => {
                const res = page.props.flash?.game_result;

                if (!res) {
                    return;
                }

                if (res.status === 'incorrect') {
                    callbacks.onIncorrect?.();

                    return;
                }

                if (res.status === 'already_completed') {
                    callbacks.onCorrect?.({ xp: 0, coins: 0 });

                    return;
                }

                callbacks.onCorrect?.({
                    xp: res.total_xp_earned || 15,
                    coins: res.coins_earned || 5,
                });
            },
        },
    );
}
