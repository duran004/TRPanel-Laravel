<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-semibold">{{ __('Files') }}</h2>
                    <hr class="my-2">
                    <div class="grid grid-cols-4 gap-4 mx-auto">
                        <div>
                            <a href="{{ route('filemanager.index') }}"
                                class="text-blue-500 hover:text-blue-700 font-semibold">
                                <img src="{{ asset('img/files.svg') }}" alt="File Manager"
                                    class="w-24 h-24 mx-auto custom-shadow">
                                <p class="text-center">{{ __('File Manager') }}</p>
                            </a>
                        </div>
                        <div class="bg-gray-100">02</div>
                        <div class="bg-gray-100">03</div>
                        <div class="bg-gray-100">04</div>
                        <div class="bg-gray-100">05</div>
                        <div class="bg-gray-100">06</div>
                        <div class="bg-gray-100">07</div>
                        <div class="bg-gray-100">08</div>
                        <div class="bg-gray-100">09</div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-semibold">{{ __('PHP') }}</h2>
                    <hr class="my-2">
                    <div class="grid grid-cols-4 gap-4 mx-auto">
                        <div>
                            <a href="{{ route('extensions.index', $user->id) }}"
                                class="text-blue-500 hover:text-blue-700 font-semibold">
                                <img src="{{ asset('img/php.svg') }}" alt="Php Eklentileri"
                                    class="w-24 h-24 mx-auto custom-shadow">
                                <p class="text-center">{{ __('Php Eklentileri') }}</p>
                            </a>
                        </div>
                        <div class="bg-gray-100">02</div>
                        <div class="bg-gray-100">03</div>
                        <div class="bg-gray-100">04</div>
                        <div class="bg-gray-100">05</div>
                        <div class="bg-gray-100">06</div>
                        <div class="bg-gray-100">07</div>
                        <div class="bg-gray-100">08</div>
                        <div class="bg-gray-100">09</div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
