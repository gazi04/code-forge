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

<div class="login-container bg-[#0d071d] text-white p-8 rounded-2xl max-w-md mx-auto border border-indigo-900/50 shadow-2xl mt-20 font-sans">

    <div class="text-center mb-8">
        <h1 class="text-3xl font-black mb-2 tracking-wide text-indigo-100">Student Portal</h1>
        <p class="text-indigo-300/60 text-sm">Enter your credentials to access the databanks.</p>
    </div>

    <form onsubmit={submitLogin} class="space-y-4">
        <div>
            <label for="email" class="block text-xs font-mono uppercase tracking-widest text-indigo-400 mb-1">Email Address</label>
            <input
                id="email"
                type="email"
                bind:value={form.email}
                required
                class="w-full bg-[#150b2e] border border-indigo-900/50 rounded-xl p-3 text-indigo-100 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-all shadow-inner"
            />
            {#if form.errors.email}
                <p class="text-rose-400 text-[11px] mt-1 font-mono font-bold">{form.errors.email}</p>
            {/if}
        </div>

        <div>
            <label for="password" class="block text-xs font-mono uppercase tracking-widest text-indigo-400 mb-1">Password</label>
            <input
                id="password"
                type="password"
                bind:value={form.password}
                required
                class="w-full bg-[#150b2e] border border-indigo-900/50 rounded-xl p-3 text-indigo-100 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-all shadow-inner"
            />
            {#if form.errors.password}
                <p class="text-rose-400 text-[11px] mt-1 font-mono font-bold">{form.errors.password}</p>
            {/if}
        </div>

        <button
            type="submit"
            disabled={form.processing}
            class="w-full mt-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold p-4 rounded-xl transition-all uppercase tracking-widest text-sm hover:shadow-[0_0_20px_rgba(99,102,241,0.4)] disabled:opacity-50"
        >
            {form.processing ? 'Authenticating...' : 'Log In'}
        </button>
    </form>
</div>
