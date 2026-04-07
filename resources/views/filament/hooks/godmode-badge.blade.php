@if(auth()->user()?->hasRole('godmode'))
    <div class="flex items-center gap-x-3">
        <div class="flex items-center gap-x-1 px-2 py-0.5 rounded-full bg-danger-600/10 text-danger-600 dark:bg-danger-500/10 dark:text-danger-500 ring-1 ring-inset ring-danger-600/10 dark:ring-danger-500/20">
            <x-heroicon-m-bolt class="w-4 h-4" />
            <span class="text-xs font-bold tracking-wide uppercase">Godmode</span>
        </div>
    </div>
@endif
