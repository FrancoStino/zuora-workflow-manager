<div>
    <x-filament::section>
        <x-slot name="heading">
            Chat with AI Assistant
        </x-slot>

        <x-slot name="description">
            Ask questions about your workflows, tasks or customers and get AI-powered responses
        </x-slot>

        <div class="flex flex-col h-[calc(100vh-26rem)]">
            {{-- Chat Messages --}}
            <div
                class="flex flex-col-reverse gap-4 flex-1 overflow-y-auto pr-2"
                x-data
                x-init="$el.scrollTop = $el.scrollHeight"
                x-on:scroll-to-bottom.window="$el.scrollTop = $el.scrollHeight"
            >
                @if($isLoading)
                    <div class="w-3/4 flex justify-start" wire:key="loading-indicator">
                        <div class="rounded-xl px-4 py-3 bg-gray-100 dark:bg-white/5 animate-pulse">
                            <div class="flex items-center gap-3">
                                <x-filament::loading-indicator class="w-5 h-5 text-primary-500"/>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium text-primary-500">AI is thinking...</span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400">Generating SQL and response</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @forelse($messages->reverse() as $message)
                    <div class="flex {{ $message->isUserMessage() ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="w-3/4 rounded-xl px-4 py-3 {{ $message->isUserMessage() ? 'bg-primary-700 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-950 dark:text-white' }}">
                            <div class="flex">
                                <div class="flex items-center gap-2 ">
                                    @if($message->isUserMessage())
                                        <x-filament::icon icon="heroicon-m-user" class="w-4 h-4"/>
                                        <span class="font-medium">You</span>
                                    @else
                                        <x-filament::icon icon="heroicon-m-cpu-chip" class="w-4 h-4"/>
                                        <span class="font-medium">Assistant</span>
                                        @if($message->hasQuery())
                                            <x-filament::badge size="sm" color="info">
                                                SQL Generated
                                            </x-filament::badge>
                                        @endif
                                    @endif
                                    @if($message->created_at)
                                        <span class="text-xs opacity-60">
                                        {{ $message->created_at->diffForHumans() }}
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    <button
                                        type="button"
                                        class="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        x-on:click="
                                                navigator.clipboard.writeText($refs.content.innerText);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                        title="Copy to clipboard"
                                    >
                                        <template x-if="!copied">
                                            <x-filament::icon icon="heroicon-o-clipboard" class="w-4 h-4"/>
                                        </template>
                                        <template x-if="copied">
                                            <x-filament::icon icon="heroicon-o-check"
                                                              class="w-4 h-4 text-green-500"/>
                                        </template>
                                    </button>
                                </div>
                            </div>

                            @if($message->isUserMessage())
                                <p class="mt-2">{{ $message->content }}</p>
                            @else
                                @php
                                    $content = $message->content;
                                    $thinkingContent = null;
                                    $mainContent = $content;
                                    if (preg_match('/<think>(.*?)<\/think>/s', $content, $matches)) {
                                        $thinkingContent = trim($matches[1]);
                                        $mainContent = trim(preg_replace('/<think>.*?<\/think>/s', '', $content));
                                    }
                                @endphp

                                @if($thinkingContent)
                                    <x-filament::section
                                        collapsible
                                        collapsed
                                        compact
                                        class="mt-3 bg-purple-50! dark:bg-purple-900/20! border-purple-200! dark:border-purple-700!"
                                    >
                                        <x-slot name="heading">
                                            <div class="flex items-center gap-2 text-purple-700 dark:text-purple-300">
                                                <x-filament::icon icon="heroicon-o-light-bulb" class="w-4 h-4"/>
                                                <span class="text-sm">Thinking</span>
                                            </div>
                                        </x-slot>

                                        <div
                                            class="text-sm text-purple-800 dark:text-purple-200 italic prose prose-sm dark:prose-invert max-w-none">
                                            {!! \Illuminate\Support\Str::markdown($thinkingContent) !!}
                                        </div>
                                    </x-filament::section>
                                @endif

                                <div
                                    class="mt-2 prose prose-sm dark:prose-invert max-w-none prose-table:w-full prose-table:border-collapse prose-thead:bg-gray-100 dark:prose-thead:bg-gray-800 prose-th:px-4 prose-th:py-3 prose-th:text-left prose-th:font-semibold prose-th:border-b prose-th:border-gray-300 dark:prose-th:border-gray-600 prose-td:px-4 prose-td:py-3 prose-td:border-b prose-td:border-gray-200 dark:prose-td:border-gray-700 prose-tr:even:bg-gray-50/50 dark:prose-tr:even:bg-gray-700/30"
                                    x-data="{ copied: false }">
                                    <div x-ref="content">
                                        {!! Str::markdown($mainContent) !!}
                                    </div>
                                </div>

                                @if($message->hasQuery())
                                    <x-filament::section
                                        collapsible
                                        collapsed
                                        compact
                                        class="mt-3"
                                    >
                                        <x-slot name="heading">
                                            <div class="flex items-center gap-2">
                                                <x-filament::icon icon="heroicon-o-code-bracket" class="w-4 h-4"/>
                                                <span class="text-sm">SQL Query</span>
                                            </div>
                                        </x-slot>

                                        <pre
                                            class="text-xs bg-gray-900 text-gray-100 p-3 rounded-lg overflow-x-auto"><code>{{ $message->query_generated }}</code></pre>
                                    </x-filament::section>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex items-center justify-center h-full min-h-62.5">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-right"
                                              class="w-16 h-16 mx-auto mb-4 opacity-40"/>
                            <p class="text-lg font-medium">Start a new conversation</p>
                            <p class="text-sm mt-1 opacity-75">Ask a question about your workflows, tasks or
                                customers.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- System Messages Panel --}}
            @if($systemMessages->isNotEmpty())
                <x-filament::section
                    collapsible
                    collapsed
                    compact
                    icon="heroicon-o-cog-6-tooth"
                >
                    <x-slot name="heading">
                        System Messages ({{ $systemMessages->count() }})
                    </x-slot>

                    <div class="max-h-50 overflow-y-auto space-y-2">
                        @foreach($systemMessages as $sysMessage)
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <x-filament::badge size="sm" color="gray">
                                    {{ strtoupper($sysMessage->role) }}
                                </x-filament::badge>
                                <p class="mt-1">
                                    @if($sysMessage->metadata)
                                        <code
                                            class="text-xs bg-gray-100 dark:bg-gray-800 p-1 rounded">{{ json_encode($sysMessage->metadata, JSON_PRETTY_PRINT) }}</code>
                                    @else
                                        {{ $sysMessage->content }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- Message Input Form --}}
            <div class="shrink-0 border-t border-gray-200 dark:border-white/10 pt-4 mt-4">
                <form wire:submit.prevent="sendMessage">
                    <div class="flex gap-4">
                        <div class="flex-auto">
                            {{ $this->form }}
                        </div>
                        @if ($this->retryAction->isVisible())
                            {{ $this->retryAction }}
                        @endif
                        {{ $this->sendAction }}
                    </div>
                </form>
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals/>
</div>
