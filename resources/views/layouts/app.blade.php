<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ?? config('app.name') }} · {{ config('app.name') }}</title>@vite(['resources/css/app.css','resources/js/app.js'])@livewireStyles</head>
@php($notificationAlertCount=app(\App\Services\NotificationService::class)->unreadCount(auth()->user()))
<body><div x-data="{open:false,notificationCount:{{ $notificationAlertCount }}}" @notification-read.window="notificationCount=Math.max(0,notificationCount-1)" class="min-h-screen lg:flex">
 <aside :class="open?'translate-x-0':'-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-ink text-white transition lg:static lg:translate-x-0">
  <div class="flex h-17 items-center gap-3 border-b border-white/10 px-5"><div class="grid size-11 place-items-center overflow-hidden rounded-xl bg-white p-1"><img src="{{ asset('logo.png') }}" alt="ShopICT logo" class="size-full object-contain"></div><div><div class="font-bold">ShopICT</div><div class="text-[10px] uppercase tracking-[.2em] text-slate-400">Retail manager</div></div><button @click="open=false" class="ml-auto lg:hidden"><i data-lucide="x" class="size-5"></i></button></div>
  <nav class="flex-1 space-y-1 overflow-y-auto p-3">
   @foreach([['dashboard','Dashboard','layout-dashboard'],['notifications','Notifications','bell'],['pos','New Sale','shopping-cart'],['orders','Orders','receipt-text'],['expenses','Expenses','wallet-cards']] as [$route,$label,$icon])
    <a wire:navigate href="{{ route($route) }}" class="nav-link {{ request()->routeIs($route) ? 'nav-active':'' }}"><i data-lucide="{{ $icon }}" class="size-4 shrink-0"></i>{{ $label }}@if($route==='notifications')<span x-cloak x-show="notificationCount>0" class="ml-auto size-2.5 rounded-full bg-red-500 ring-2 ring-red-500/20"></span>@endif</a>
   @endforeach

   <div x-data="{inventoryOpen: @js(request()->routeIs('inventory*'))}" class="rounded-xl">
    <button type="button" @click="inventoryOpen=!inventoryOpen" class="nav-link w-full" :class="inventoryOpen || {{ request()->routeIs('inventory*') ? 'true' : 'false' }} ? 'bg-white/10 text-white' : ''" aria-label="Toggle inventory menu">
     <i data-lucide="warehouse" class="size-4 shrink-0"></i><span class="flex-1 text-left">Inventory</span><i data-lucide="chevron-down" class="size-4 shrink-0 transition-transform duration-200" :class="inventoryOpen?'rotate-180':''"></i>
    </button>
    <div x-cloak x-show="inventoryOpen" x-transition.origin.top class="mx-2 mt-1 space-y-1 rounded-xl border border-white/10 bg-black/10 p-1.5">
     <a wire:navigate href="{{ route('inventory.section',['section'=>'actions']) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-xs transition {{ request()->route('section')==='actions'?'bg-brand-500 font-semibold text-white shadow-sm':'text-slate-300 hover:bg-white/10 hover:text-white' }}"><i data-lucide="package-plus" class="size-3.5"></i>Actions</a>
     <a wire:navigate href="{{ route('inventory.section',['section'=>'records']) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-xs transition {{ request()->route('section')==='records'?'bg-brand-500 font-semibold text-white shadow-sm':'text-slate-300 hover:bg-white/10 hover:text-white' }}"><i data-lucide="clipboard-list" class="size-3.5"></i>Records</a>
    </div>
   </div>

   <a wire:navigate href="{{ route('products') }}" class="nav-link {{ request()->routeIs('products') ? 'nav-active':'' }}"><i data-lucide="package" class="size-4 shrink-0"></i>Products</a>

   @if(auth()->user()->isAdmin())
    <div class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[.18em] text-slate-500">Admin</div>
    <a wire:navigate href="{{ route('finance') }}" class="nav-link {{ request()->routeIs('finance*') ? 'nav-active':'' }}"><i data-lucide="landmark" class="size-4 shrink-0"></i>Finance</a>
    <a wire:navigate href="{{ route('reports') }}" class="nav-link {{ request()->routeIs('reports') ? 'nav-active':'' }}"><i data-lucide="chart-no-axes-combined" class="size-4 shrink-0"></i>Reports</a>
    @foreach([['suppliers','Suppliers','truck'],['resellers','Resellers','users-round']] as [$route,$label,$icon])<a wire:navigate href="{{ route($route) }}" class="nav-link {{ request()->routeIs($route) ? 'nav-active':'' }}"><i data-lucide="{{ $icon }}" class="size-4 shrink-0"></i>{{ $label }}</a>@endforeach
   @endif
  </nav>
  <div class="border-t border-white/10 p-3"><div class="mb-2 rounded-xl bg-white/5 p-3"><div class="truncate text-xs font-semibold">{{ auth()->user()->email }}</div><div class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">{{ auth()->user()->role }}</div></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link w-full"><i data-lucide="log-out" class="size-4"></i>Sign out</button></form></div>
 </aside>
 <div x-show="open" @click="open=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>
 <section class="min-w-0 flex-1"><header class="sticky top-0 z-30 flex h-15 items-center border-b bg-white/95 px-4 shadow-sm backdrop-blur lg:hidden"><button @click="open=true"><i data-lucide="menu" class="size-5"></i></button><img src="{{ asset('logo.png') }}" alt="ShopICT logo" class="ml-3 size-9 object-contain"><strong class="ml-2">ShopICT</strong><a wire:navigate href="{{ route('notifications') }}" class="relative ml-auto grid size-9 place-items-center rounded-xl border border-slate-200 text-slate-600" aria-label="Notifications"><i data-lucide="bell" class="size-5"></i><span x-cloak x-show="notificationCount>0" class="absolute right-1 top-1 size-2.5 rounded-full bg-red-600 ring-2 ring-white"></span></a></header><main class="mx-auto max-w-[1500px] p-4 sm:p-6 lg:p-8">@if(session('status'))<div class="mb-4 rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">{{ session('status') }}</div>@endif{{ $slot }}</main></section>
</div>@livewireScripts</body></html>
