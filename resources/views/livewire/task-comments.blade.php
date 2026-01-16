<div class="mt-8 bg-white rounded-xl border border-gray-200 shadow-sm p-6 dark:bg-gray-900 dark:border-gray-800">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <x-heroicon-m-chat-bubble-left-right class="w-5 h-5 text-primary-600" />
        Diskusi & Komentar
    </h3>

    <!-- Comment List -->
    <div class="space-y-6 mb-6 max-h-[400px] overflow-y-auto">
        @forelse($comments as $comment)
            <div class="flex gap-4">
                <div class="flex-shrink-0">
                    <img src="{{ $comment->user->getFilamentAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment->user->name) . '&color=7F9CF5&background=EBF4FF' }}" class="w-10 h-10 rounded-full border border-gray-200 dark:border-gray-700" alt="{{ $comment->user->name }}">
                </div>
                <div class="flex-1">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 relative">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-medium text-sm text-gray-900 dark:text-white">
                                {{ $comment->user->name }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $comment->created_at->diffForHumans() }}
                            </span>
                        </div>
                        
                        <!-- Body -->
                        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" style="white-space: pre-line;">
                            {{ $comment->body }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                Belum ada komentar. Mulai diskusi sekarang.
            </div>
        @endforelse
    </div>
<br />
    <!-- Input Form -->
    <form wire:submit.prevent="submit" class="relative">
        <textarea 
            wire:model.defer="body" 
            placeholder="Tulis komentar..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-primary-500 focus:border-primary-500 resize-none"
            rows="3"
            wrap="soft"
        ></textarea>
        
        <div class="flex justify-end mt-2">
             <x-filament::button type="submit" size="sm" icon="heroicon-m-paper-airplane">
                Kirim
            </x-filament::button>
        </div>
    </form>
</div>
