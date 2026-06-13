<script>
    import { useForm } from '@inertiajs/svelte';

    const form = useForm({
        name: '',
        forename: '',
        lastname: '',
        birthday: '',
        gender: 'male',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submitRegister(e) {
        e.preventDefault();

        form.post('/register/student', {
            preserveScroll: true,
            onError: () => form.reset('password', 'password_confirmation'),
        });
    }
</script>

<div class="min-h-screen bg-[#080510] flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm">

        <!-- Brand -->
        <div class="text-center mb-8">
            <p class="text-xs font-mono uppercase tracking-[0.3em] text-indigo-400/60 mb-3">Arcane.dev</p>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Create Account</h1>
            <p class="text-sm font-mono text-white/25 mt-2 uppercase tracking-widest">Join the academy</p>
        </div>

        <!-- Card -->
        <div class="bg-white/[0.03] border border-white/[0.07] rounded-2xl p-6 shadow-[0_0_60px_rgba(99,102,241,0.06)]">
            <form onsubmit={submitRegister} class="space-y-5">
                <div>
                    <label for="name" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                        Username
                    </label>
                    <input
                        id="name"
                        type="text"
                        bind:value={form.name}
                        required
                        placeholder="agent_x"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                    />
                    {#if form.errors.name}
                        <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.name}</p>
                    {/if}
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="forename" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                            First Name
                        </label>
                        <input
                            id="forename"
                            type="text"
                            bind:value={form.forename}
                            required
                            placeholder="Ada"
                            class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                        />
                        {#if form.errors.forename}
                            <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.forename}</p>
                        {/if}
                    </div>

                    <div>
                        <label for="lastname" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                            Last Name
                        </label>
                        <input
                            id="lastname"
                            type="text"
                            bind:value={form.lastname}
                            required
                            placeholder="Lovelace"
                            class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                        />
                        {#if form.errors.lastname}
                            <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.lastname}</p>
                        {/if}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="birthday" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                            Birthday
                        </label>
                        <input
                            id="birthday"
                            type="date"
                            bind:value={form.birthday}
                            required
                            class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all [color-scheme:dark]"
                        />
                        {#if form.errors.birthday}
                            <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.birthday}</p>
                        {/if}
                    </div>

                    <div>
                        <label for="gender" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                            Gender
                        </label>
                        <select
                            id="gender"
                            bind:value={form.gender}
                            required
                            class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all [color-scheme:dark]"
                        >
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        {#if form.errors.gender}
                            <p class="text-rose-400 text-[11px] mt-1.5 font-mono font-bold">{form.errors.gender}</p>
                        {/if}
                    </div>
                </div>

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

                <div>
                    <label for="password_confirmation" class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/70 mb-2">
                        Confirm Password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        bind:value={form.password_confirmation}
                        required
                        placeholder="••••••••"
                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3 text-sm text-white placeholder-white/20 font-mono focus:border-indigo-500/60 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 transition-all"
                    />
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full mt-2 py-3 rounded-xl font-black text-sm uppercase tracking-widest transition-all
                        bg-indigo-600 text-white hover:bg-indigo-500 hover:shadow-[0_0_24px_rgba(99,102,241,0.35)]
                        disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]"
                >
                    {form.processing ? 'Creating...' : 'Create Account'}
                </button>
            </form>
        </div>

        <p class="text-center text-xs font-mono text-white/30 mt-6">
            Already have an account?
            <a href="/login" class="text-indigo-400/80 hover:text-indigo-300 font-bold transition-colors">Sign in</a>
        </p>

    </div>
</div>
