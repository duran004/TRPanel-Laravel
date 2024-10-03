<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">{{ $user->name }} için PHP Eklentileri</h1>

        <!-- PHP Extension Tablo -->
        <form id="bulk-action-form" action="{{ route('extensions.bulkAction') }}" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white shadow-md rounded-lg">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">
                                <input type="checkbox" id="select-all" class="form-checkbox">
                            </th>
                            <th class="px-4 py-2 text-left">Eklenti Adı</th>
                            <th class="px-4 py-2 text-left">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($extensions as $extension)
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <input type="checkbox" name="selected_extensions[]" value="{{ $extension->id }}"
                                        class="form-checkbox extension-checkbox">
                                </td>
                                <td class="px-4 py-2">{{ $extension->name }}</td>
                                <td class="px-4 py-2">
                                    @if ($extension->is_enabled)
                                        <span
                                            class="inline-block bg-green-100 text-green-800 px-2 py-1 text-xs font-semibold rounded">Aktif</span>
                                    @else
                                        <span
                                            class="inline-block bg-gray-100 text-gray-800 px-2 py-1 text-xs font-semibold rounded">Devre
                                            Dışı</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">Henüz bir eklenti eklenmedi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Toplu İşlem Butonları -->
            <div class="mt-4 flex space-x-2">
                <button type="submit" name="action" value="activate"
                    class="bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-600">Seçileni Aktif
                    Et</button>
                <button type="submit" name="action" value="deactivate"
                    class="bg-yellow-500 text-white font-bold py-2 px-4 rounded hover:bg-yellow-600">Seçileni Devre Dışı
                    Bırak</button>
                <button type="submit" name="action" value="delete"
                    class="bg-red-500 text-white font-bold py-2 px-4 rounded hover:bg-red-600">Seçileni Sil</button>
            </div>
        </form>
    </div>

    <script>
        // Tümünü Seç/Deselect Checkbox
        document.getElementById('select-all').addEventListener('click', function(event) {
            let checkboxes = document.querySelectorAll('.extension-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = event.target.checked);
        });
    </script>
</x-app-layout>
