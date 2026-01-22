<x-layouts.app :title="$message . ' - ' . config('app.name', 'Zuora Workflow Manager')">
    <x-slot:head title="Maintenance">
        <style>
            @keyframes pulse-slow {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.7;
                }
            }

            .animate-pulse-slow {
                animation: pulse-slow 3s ease-in-out infinite;
            }
        </style>
    </x-slot:head>

    {{-- Maintenance Card --}}
    <div
        class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl ring-1 ring-gray-900/5 dark:bg-gray-800 dark:ring-white/10">
        {{-- Icon --}}
        <div
            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <svg
                class="h-10 w-10 text-amber-600 dark:text-amber-400 animate-pulse-slow"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"
                />
            </svg>
        </div>

        {{-- Message --}}
        <h1 class="mb-3 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            {{ $message }}
        </h1>

        {{-- Description --}}
        <p class="mb-6 text-center text-base text-gray-600 dark:text-gray-400">
            {{ $description }}
        </p>

        {{-- Status indicator --}}
        <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <span class="relative flex h-3 w-3">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500"></span>
            </span>
            <span>Work in progress</span>
        </div>
    </div>
</x-layouts.app>
