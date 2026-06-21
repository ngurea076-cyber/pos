<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · ShopICT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden flex-col justify-between bg-ink p-12 text-white lg:flex">
        <div class="flex items-center gap-3"><span class="grid size-16 place-items-center rounded-2xl bg-white p-1.5"><img src="{{ asset('logo.png') }}" alt="ShopICT logo" class="size-full object-contain"></span><span class="text-xl font-bold">ShopICT</span></div>
        <div><h1 class="text-5xl font-bold leading-tight">Run the till.<br><span class="text-brand-500">Watch the margin.</span></h1><p class="mt-5 max-w-lg text-slate-400">Point-of-sale and serialized inventory for growing Kenyan retailers.</p></div>
        <p class="text-xs text-slate-500">ShopICT · KES</p>
    </section>

    <section class="flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <img src="{{ asset('logo.png') }}" alt="ShopICT logo" class="mx-auto mb-6 h-24 w-auto object-contain lg:hidden">
            <form method="POST" action="{{ route('login') }}" class="card space-y-4 p-6">
                @csrf
                <div><h1 class="text-xl font-bold text-ink">Sign in</h1><p class="mt-1 text-sm text-slate-500">Use your assigned ShopICT account.</p></div>
                <div><label class="label" for="email">Email</label><input id="email" class="input" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
                <div><label class="label" for="password">Password</label><input id="password" class="input" name="password" type="password" required autocomplete="current-password"></div>
                @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
                <button type="submit" class="btn-primary w-full">Sign in</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
