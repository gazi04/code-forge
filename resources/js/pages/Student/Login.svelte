<script>
    import { useForm } from '@inertiajs/svelte';

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submitLogin(e) {
        e.preventDefault();

        form.post('/login/student', {
            preserveScroll: true,
        });
    }
</script>

<div class="min-h-screen bg-[#080510] flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

        <!-- Brand -->
        <div class="text-center mb-10">
            <p class="text-xs font-mono uppercase tracking-[0.3em] text-indigo-400/60 mb-3">Arcane.dev</p>
            <h1 class="text-3xl font-black text-white tracking-tight">Student Portal</h1>
            <p class="text-sm font-mono text-white/25 mt-2 uppercase tracking-widest">Enter your credentials</p>
        </div>

        <!-- Card -->
        <div class="bg-white/[0.03] border border-white/[0.07] rounded-2xl p-6 shadow-[0_0_60px_rgba(99,102,241,0.06)]">
            <form onsubmit={submitLogin} class="space-y-5">
                <div>
                    <label for="email" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        bind:value={form.email}
                        required
                        placeholder="you@example.com"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                    />
                    {#if form.errors.email}
                        <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.email}</p>
                    {/if}
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        bind:value={form.password}
                        required
                        placeholder="••••••••"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                    />
                    {#if form.errors.password}
                        <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.password}</p>
                    {/if}
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full mt-2 py-3 rounded-xl font-black text-sm uppercase tracking-widest transition-all
                        bg-indigo-600 text-white hover:bg-indigo-500 hover:shadow-[0_0_24px_rgba(99,102,241,0.35)]
                        disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]"
                >
                    {form.processing ? 'Authenticating...' : 'Log In'}
                </button>
            </form>
        </div>

    </div>
</div>
