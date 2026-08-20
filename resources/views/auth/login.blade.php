<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | POS Jamu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-[#153b2a] px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-emerald-400/15"></div>
            <div class="absolute -bottom-32 -left-16 h-72 w-72 rounded-full border-[36px] border-emerald-300/10"></div>

            <a href="{{ url('/') }}" class="relative flex items-center gap-3 self-start text-white no-underline">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-400 text-lg font-black shadow-lg shadow-emerald-950/25">PJ</span>
                <span>
                    <b class="block text-base font-extrabold tracking-tight">POS Jamu</b>
                    <small class="block text-xs text-emerald-100/70">Sistem Penjualan</small>
                </span>
            </a>

            <div class="relative max-w-md">
                <span class="mb-5 inline-flex rounded-full border border-emerald-200/20 bg-emerald-300/10 px-3 py-1 text-[11px] font-bold tracking-wide text-emerald-100">OPERASIONAL USAHA</span>
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">Kelola penjualan dengan lebih teratur.</h1>
                <p class="mt-5 max-w-sm text-sm leading-7 text-emerald-50/75">Akses transaksi, stok, dan laporan usaha Anda dalam satu sistem yang aman.</p>
            </div>

            <p class="relative text-xs text-emerald-100/55">&copy; {{ now()->year }} POS Jamu. Semua hak dilindungi.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-14 xl:px-24">
            <div class="w-full max-w-[420px]">
                <a href="{{ url('/') }}" class="mb-12 flex items-center gap-3 text-slate-800 no-underline lg:hidden">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-600 text-sm font-black text-white shadow-lg shadow-emerald-700/20">PJ</span>
                    <span><b class="block text-sm font-extrabold">POS Jamu</b><small class="block text-[11px] text-slate-400">Sistem Penjualan</small></span>
                </a>

                <div class="mb-8">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-600">Selamat datang</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">Masuk ke akun Anda</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Silakan masukkan data akun untuk melanjutkan ke sistem.</p>
                </div>

                <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5" novalidate>
                    @csrf

                    <div>
                        <label for="email" class="label">Email</label>
                        <input id="email" class="input @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
                        @error('email')
                            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <input id="password" class="input" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Ingat saya di perangkat ini</span>
                    </label>

                    <button class="btn-primary w-full py-3" type="submit">Masuk ke sistem</button>
                </form>

                <p class="mt-8 border-t border-slate-100 pt-5 text-center text-xs leading-5 text-slate-400">Hubungi administrator jika Anda memerlukan bantuan untuk mengakses akun.</p>
            </div>
        </section>
    </main>
</body>
</html>
