@php
    $user = filament()->auth()->user();
    $plugin = filament('filament-greeter');
    $name = $plugin->getName() ?? filament()->getUserName($user);
    $title = $plugin->getTitle() ?? __('greeter::widget.title');
    $message = $plugin->getMessage() ?? __('greeter::widget.message');
    $avatarSize = $plugin->getAvatarSize();
    $avatarUrl = $plugin->getAvatarUrl();
    $action = $plugin->getAction();
@endphp

<x-filament-widgets::widget class="fi-greeter-widget">
    <x-filament::section class="flex flex-col gap-4">
        <div class="flex items-center gap-4">
            @if ($plugin->shouldHaveAvatar())
                <x-filament-panels::avatar.user
                    :src="$avatarUrl ?? filament()->getUserAvatarUrl($user)"
                    :size="$avatarSize"
                    :user="$user"
                    
                />
            @endif

            <div class="flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-x-2">
                    <p class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                        {{ trim($message) }}
                    </p>

                    <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                        {{ $name }}
                    </h2>
                </div>

                @if ($plugin->shouldHaveTitle())
                    <p class="text-md text-gray-500 dark:text-gray-400">
                        {{ $title }}
                    </p>
                @endif
            </div>

            @if ($action instanceof \Filament\Actions\Action)
                @if ($action->isVisible())
                    {{ $action->color('gray')->button()->labeledFrom('md') }}
                @endif
            @else
                <form
                    action="{{ filament()->getLogoutUrl() }}"
                    method="post"
                    class="shrink-0"
                >
                    @csrf

                    <x-filament::button
                        color="gray"
                        icon="heroicon-m-arrow-left-on-rectangle"
                        labeled-from="md"
                        tag="button"
                        type="submit"
                    >
                        {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                    </x-filament::button>
                </form>
            @endif
        </div>

        <div class="flex flex-wrap gap-6 text-sm text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/9 mt-2">
            <div class="flex items-start gap-2">
                <x-heroicon-m-envelope class="h-4 w-4 text-primary-500" />
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        Email
                    </p>
                    <p class="text-xs font-normal text-gray-700 dark:text-gray-200">
                        {{ $user->email }}
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <x-heroicon-m-calendar-days class="h-4 w-4 text-primary-500" />
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        Bergabung sejak
                    </p>
                    <p class="text-xs  font-normal text-gray-700 dark:text-gray-200">
                        {{ $user->created_at?->timezone(config('app.timezone'))->isoFormat('DD MMM YYYY') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
