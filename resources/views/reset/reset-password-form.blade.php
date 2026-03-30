@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10 bg-[radial-gradient(circle_at_center,_#031b4e_0%,_#02153d_35%,_#010f2e_70%,_#000b22_100%)]">
    <div class="w-full max-w-2xl">
        <div class="rounded-3xl border border-cyan-400/20 bg-[#051a47]/90 shadow-[0_0_35px_rgba(0,200,255,0.35)] px-8 py-10 md:px-12 md:py-12">
            <h1 class="text-white text-4xl md:text-5xl font-extrabold tracking-tight mb-3">
                Reset Password
            </h1>

            <p class="text-cyan-100/85 text-sm md:text-base mb-8">
                Enter your new password below to reset your account password.
            </p>

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">
                <input type="hidden" name="email" value="{{ old('email', $email ?? request('email')) }}">

                <div>
                    <label for="email_display" class="block text-cyan-100 text-sm mb-2">
                        Email Address
                    </label>
                    <input
                        id="email_display"
                        type="email"
                        value="{{ old('email', $email ?? request('email')) }}"
                        readonly
                        class="w-full rounded-2xl border border-cyan-400/40 bg-slate-200 text-slate-800 px-5 py-4 text-sm md:text-base outline-none cursor-not-allowed"
                    >
                </div>

                <div>
                    <label for="password" class="block text-cyan-100 text-sm mb-2">
                        New Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Enter new password"
                        class="w-full rounded-2xl border border-cyan-400/40 bg-[#041536] text-white placeholder:text-cyan-100/40 px-5 py-4 text-sm md:text-base outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/30 @error('password') border-red-400 ring-2 ring-red-400/30 @enderror"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-cyan-100 text-sm mb-2">
                        Confirm New Password
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm new password"
                        class="w-full rounded-2xl border border-cyan-400/40 bg-[#041536] text-white placeholder:text-cyan-100/40 px-5 py-4 text-sm md:text-base outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/30"
                    >
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-4 text-white font-semibold text-sm md:text-base shadow-[0_10px_30px_rgba(0,180,255,0.35)] transition hover:scale-[1.01] hover:shadow-[0_12px_35px_rgba(0,180,255,0.45)]"
                    >
                        Reset Password
                    </button>
                </div>

                <div class="text-center pt-2">
                    <a
                        href="{{ route('login') }}"
                        class="text-cyan-300 text-sm hover:text-cyan-200 transition"
                    >
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection