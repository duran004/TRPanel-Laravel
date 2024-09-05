<x-app-layout>

    <div class="container mx-auto p-4">
        <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-red-500 text-white text-center py-4">
                <h4 class="text-2xl font-bold">Hata</h4>
            </div>
            <div class="p-4">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Hata!</strong>
                    <span class="block sm:inline">{{ $message }}</span>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
