@php use Illuminate\Support\Facades\Storage; @endphp
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
                        <div class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed" style="white-space: pre-line;">
                            {{ $comment->body }}
                        </div>

                        <!-- Attachments -->
                        @if($comment->attachments && count($comment->attachments) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($comment->attachments as $attachment)
                                        @if(\App\Models\TaskComment::isImage($attachment))
                                            {{-- Image Thumbnail (64x64px) --}}
                                            <a href="{{ Storage::disk('public')->url($attachment) }}" target="_blank" class="block">
                                                <img 
                                                    src="{{ Storage::disk('public')->url($attachment) }}" 
                                                    alt="Attachment"
                                                    class="w-16 h-16 object-cover rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary-500 hover:opacity-80 transition-all cursor-pointer"
                                                >
                                            </a>
                                        @else
                                            {{-- Document Icon (64x64px) --}}
                                            <a href="{{ Storage::disk('public')->url($attachment) }}" target="_blank" 
                                               class="flex flex-col items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-primary-500 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                                               title="{{ \App\Models\TaskComment::getFileName($attachment) }}">
                                                <x-dynamic-component :component="\App\Models\TaskComment::getFileIcon($attachment)" class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                                                <span class="text-[8px] text-gray-500 dark:text-gray-400 uppercase mt-0.5">
                                                    {{ strtoupper(pathinfo($attachment, PATHINFO_EXTENSION)) }}
                                                </span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
        
        <!-- File Upload Preview -->
        @if(count($attachments) > 0)
            <div class="flex flex-wrap gap-2 mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                @foreach($attachments as $index => $attachment)
                    <div class="relative group">
                        @if(in_array(strtolower($attachment->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']))
                            <img src="{{ $attachment->temporaryUrl() }}" class="w-16 h-16 object-cover rounded-lg border border-gray-300 dark:border-gray-600" alt="Preview">
                        @else
                            <div class="flex flex-col items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600">
                                <x-heroicon-o-document class="w-5 h-5 text-gray-500" />
                                <span class="text-[8px] text-gray-500 uppercase">{{ strtoupper($attachment->getClientOriginalExtension()) }}</span>
                            </div>
                        @endif
                        <button 
                            type="button" 
                            wire:click="removeAttachment({{ $index }})"
                            class="w-6 h-6 bg-gray-500 dark:bg-gray-600 text-white rounded-full flex items-center justify-center hover:bg-gray-600 dark:hover:bg-gray-500 shadow-md"
                            style="position: absolute; top: -8px; right: -8px; z-index: 10;"
                        >
                            <x-heroicon-m-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex justify-between items-center mt-2">
            <!-- File Input -->
            <label class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 cursor-pointer hover:text-primary-600 transition-colors">
                <x-heroicon-o-paper-clip class="w-5 h-5" />
                <span>Lampiran</span>
                <input 
                    type="file" 
                    wire:model="newAttachments" 
                    multiple 
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
                    class="hidden"
                >
            </label>

            <x-filament::button type="submit" size="sm" icon="heroicon-m-paper-airplane">
                Kirim
            </x-filament::button>
        </div>

        <!-- Upload Progress -->
        <div wire:loading wire:target="newAttachments" class="mt-2">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="animate-spin h-4 w-4 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Mengupload...</span>
            </div>
        </div>
    </form>
</div>
