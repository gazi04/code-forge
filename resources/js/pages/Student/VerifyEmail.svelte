<script>
    import { router, useForm } from '@inertiajs/svelte';

    let { status } = $props();

    const resendForm = useForm({});

    function resend(e) {
        e.preventDefault();
        resendForm.post('/email/verification-notification', {
            preserveScroll: true,
        });
    }

    function logout(e) {
        e.preventDefault();
        router.post('/logout');
    }
</script>

<div class="min-h-screen bg-[#080510] flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <!-- Brand -->
        <div class="text-center mb-10">
            <p
                class="text-xs font-mono uppercase tracking-[0.3em] text-indigo-400/60 mb-3"
            >
                Arcane.dev
            </p>
            <h1
                class="text-2xl sm:text-3xl font-black text-white tracking-tight"
            >
                Verify Your Email
            </h1>
            <p
                class="text-sm font-mono text-white/25 mt-2 uppercase tracking-widest"
            >
                One quick step
            </p>
        </div>

        {#if status === 'verification-link-sent'}
            <div
                class="mb-6 rounded-xl border border-emerald-400/20 bg-emerald-400/[0.06] px-4 py-3 text-center text-sm text-emerald-200/90"
            >
                A fresh verification link has been sent to your email.
            </div>
        {/if}

        <!-- Card -->
        <div
            class="bg-white/[0.03] border border-white/[0.07] rounded-2xl p-6 shadow-[0_0_60px_rgba(99,102,241,0.06)]"
        >
            <p class="text-sm text-white/60 leading-relaxed text-center">
                We've sent a verification link to your email address. Click it
                to unlock your adventure. Didn't get it? Request a new one
                below.
            </p>

            <form onsubmit={resend} class="mt-6">
                <button
                    type="submit"
                    disabled={resendForm.processing}
                    class="w-full py-3 rounded-xl font-black text-sm uppercase tracking-widest transition-all
                        bg-indigo-600 text-white hover:bg-indigo-500 hover:shadow-[0_0_24px_rgba(99,102,241,0.35)]
                        disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]"
                >
                    {resendForm.processing
                        ? 'Sending...'
                        : 'Resend Verification Email'}
                </button>
            </form>
        </div>

        <p class="text-center text-xs font-mono text-white/30 mt-6">
            Wrong account?
            <a
                href="/logout"
                onclick={logout}
                class="text-indigo-400/80 hover:text-indigo-300 font-bold transition-colors"
                >Log out</a
            >
        </p>
    </div>
</div>
