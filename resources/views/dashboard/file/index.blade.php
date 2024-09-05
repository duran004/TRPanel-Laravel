<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('File Manager') }}
        </h2>
    </x-slot>







    <div class="container max-w-7xl mx-auto bg-gray-100 px-3 py-0">
        <div class="flex justify-between items-center ">
            <div class="flex items center my-3">
                <x-a href="{{ route('filemanager.index') }}"><i class="fas fa-home"></i>
                    {{ __('Home') }}</x-a>
                <x-a href="#" id="upOneLevel" :class="$isOneUpLevel ? '' : 'disabled'">
                    <i class="fas fa-arrow-up"></i>
                    {{ __('Up One Level') }}
                </x-a>

                <form action="{{ route('filemanager.folder.create', ['path' => request()->get('path')]) }}"
                    method="GET" class="formajax_popup">
                    <x-button>
                        <i class="fas fa-plus"></i> {{ __('Folder') }}
                    </x-button>
                </form>

                <form action="{{ route('filemanager.file.create', ['path' => request()->get('path')]) }}" method="GET"
                    class="formajax_popup">
                    <x-button>
                        <i class="fas fa-plus"></i> {{ __('File') }}
                    </x-button>
                </form>
                <form action="{{ route('filemanager.upload', ['path' => request()->get('path')]) }}" method="GET"
                    class="formajax_popup">
                    <x-button>
                        <i class="fas fa-upload"></i> {{ __('Upload') }}
                    </x-button>
                </form>

                <form action="{{ route('filemanager.download', ['path' => request()->get('path')]) }}" method="POST"
                    class="">
                    @csrf
                    <x-input type="hidden" name="_files" id="download_files" />
                    <x-button id="download-btn" class="disabled">
                        <i class="fas fa-download"></i> {{ __('Download') }}
                    </x-button>
                </form>


                <form action="{{ route('filemanager.file.destroy', ['path' => request()->get('path')]) }}"
                    method="POST" class="formajax_delete">
                    @csrf
                    @method('DELETE')
                    <x-input type="hidden" name="_files" id="trash_files" />
                    <x-button id="trash-btn" class="disabled">
                        <i class="fas fa-trash"></i> {{ __('Delete') }}
                    </x-button>
                </form>

            </div>

        </div>
        <div class="flex">
            <div class="w-1/5 bg-gray-200 p-3">
                <div class="flex items center text-sm">
                    <form action="{{ route('filemanager.index') }}" method="GET" class="flex items-center">
                        <x-input type="text" name="path" placeholder="Enter Path"
                            value="{{ request()->get('path') }}" />
                        <x-button>{{ __('Go') }}</x-button>
                    </form>
                </div>
                <hr class="my-2">
                <h1 class="text-lg font-bold">Directories</h1>
                <ul class="mt-2">
                    @foreach ($directories as $directory)
                        <li class="double_click cursor-pointer bg-gray-300 hover:bg-gray-400 text-sm border-solid px-2 "
                            data-path="{{ $directory->path }}"> <i class="fas fa-folder"></i>
                            {{ $directory->name }}
                        </li>
                    @endforeach
                </ul>
            </div>



            <div class="w-4/5">
                <table class="border-collapse table-auto w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Size
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Modified
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Permissions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($files as $file)
                            <tr class="selectable-row context-menu" data-path="{{ $file->path }}">

                                <td class="px-6 py-1 whitespace-nowrap">
                                    <input type="checkbox" class="file-checkbox" data-path="{{ $file->path }}">
                                </td>
                                <td scope="col"
                                    class="px-6 py-1 whitespace-nowrap @if (is_dir($file->path)) double_click cursor-pointer @endif"
                                    data-path="{{ $file->path }}">
                                    @if (is_dir($file->path))
                                        <div class="dir cursor-pointer" data-path="{{ $file->path }}"></div>
                                    @else
                                        <div class="file"></div>
                                    @endif
                                </td>
                                <td class="px-6 py-1 whitespace-nowrap @if (is_dir($file->path)) double_click cursor-pointer @endif"
                                    data-path="{{ $file->path }}">
                                    {{ $file->name }}</td>
                                <td class="px-6 py-1 whitespace-nowrap">{{ $file->size }}</td>
                                <td class="px-6 py-1 whitespace-nowrap">{{ $file->last_modified }}</td>
                                <td class="px-6 py-1 whitespace-nowrap">{{ $file->type }}</td>
                                <td class="px-6 py-1 whitespace-nowrap">{{ $file->permissions }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div id="context-menu" class="hidden bg-white shadow-md rounded-md py-2">
            <a href="#" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200">Open</a>
            <a href="#" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200">Download</a>
            <a href="#" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200">Delete</a>
        </div>
    </div>
    <script>
        /**
         * Up One Level
         */
        document.getElementById('upOneLevel').addEventListener('click', function() {
            const path = "{{ addslashes(request()->get('path')) }}";
            const segments = path.split('\\');
            segments.pop();
            const parentPath = segments.join('\\');
            window.location.href = '/filemanager?path=' + parentPath;
        });
        /**
         * Double Click to Open Directory
         */
        function initializeDoubleClick() {
            const dirs = document.querySelectorAll('.double_click');
            console.log('Directories:', dirs);
            dirs.forEach(function(dir) {
                dir.addEventListener('dblclick', function() {
                    const dirPath = this.getAttribute('data-path');
                    console.log('Selected Directory Path:', dirPath);
                    const normalizedPath = normalizePath(dirPath);
                    window.location.href = '/filemanager?path=' + normalizedPath;
                });
            });
        }

        function toggleRowHighlight(row, isSelected) {
            if (isSelected) {
                row.classList.add('bg-gray-200');
            } else {
                row.classList.remove('bg-gray-200');
            }
        }

        function normalizePath(path) {
            const isWindows = path.includes("\\");
            const segments = path.split(isWindows ? /[\\/]/ : "/");
            const normalizedSegments = [];

            segments.forEach(segment => {
                if (segment === "..") {
                    if (normalizedSegments.length > 0) {
                        normalizedSegments.pop();
                    }
                } else if (segment !== "." && segment !== "") {
                    normalizedSegments.push(segment);
                }
            });

            return (isWindows ? normalizedSegments.join("\\") : normalizedSegments.join("/"));
        }

        /**
         * Context Menu
         */
        function initializeContextMenu() {
            const contextMenu = document.getElementById('context-menu');
            const files = document.querySelectorAll('.context-menu');

            files.forEach(function(file) {
                file.addEventListener('contextmenu', function(event) {
                    event.preventDefault();
                    const filePath = this.getAttribute('data-path');
                    unselectAllFiles();
                    selectRow(this);
                    showContextMenu(event, filePath);
                });
            });

            function unselectAllFiles() {
                const checkedfiles = document.querySelectorAll('.file-checkbox:checked');
                checkedfiles.forEach(function(checkbox) {
                    checkbox.checked = false;
                    toggleRowHighlight(checkbox.closest('tr'), false);
                });

            }

            function selectRow(row) {
                const selectedRow = document.querySelector('.bg-gray-200');
                if (selectedRow) {
                    selectedRow.classList.remove('bg-gray-200');
                }
                row.classList.add('bg-gray-200');
                //checked
                const checkbox = row.querySelector('.file-checkbox');
                checkbox.checked = true;

            }

            document.addEventListener('click', function() {
                contextMenu.classList.add('hidden');
            });

            function showContextMenu(event, filePath) {
                contextMenu.style.top = `${event.pageY}px`;
                contextMenu.style.left = `${event.pageX}px`;
                contextMenu.classList.remove('hidden');
                console.log('File Path:', filePath);

                contextMenu.querySelectorAll('a').forEach(function(menuItem) {
                    menuItem.onclick = function() {
                        handleMenuItemClick(menuItem.textContent, filePath);
                    };
                });
            }

            function handleMenuItemClick(action, filePath) {
                console.log(`Action: ${action}, File Path: ${filePath}`);
                // Implement your action handling logic here
                contextMenu.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeDoubleClick();
            initializeContextMenu();
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                initializeDoubleClick();
                initializeContextMenu();
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('select-all');
            const fileCheckboxes = document.querySelectorAll('.file-checkbox');
            const selectableRows = document.querySelectorAll('.selectable-row');
            const downloadBtn = document.getElementById('download-btn');
            const trashBtn = document.getElementById('trash-btn');

            selectAllCheckbox.addEventListener('change', function() {
                fileCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                    toggleRowHighlight(checkbox.closest('tr'), checkbox.checked);
                });
                toggleActionButtons();
                selectedFilesProcess();
            });

            fileCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    toggleRowHighlight(checkbox.closest('tr'), checkbox.checked);
                    toggleActionButtons();
                    selectedFilesProcess();
                });
            });

            selectableRows.forEach(row => {
                row.addEventListener('click', function(event) {
                    if (event.target.tagName !== 'INPUT') {
                        const checkbox = row.querySelector('.file-checkbox');
                        checkbox.checked = !checkbox.checked;
                        toggleRowHighlight(row, checkbox.checked);
                        toggleActionButtons();
                        selectedFilesProcess();
                    }
                });
            });

            downloadBtn.addEventListener('click', function() {
                const selectedFiles = getSelectedFiles();
                console.log('Selected files for download:', selectedFiles);
                // Implement your download logic here
            });

            trashBtn.addEventListener('click', function() {
                const selectedFiles = getSelectedFiles();
                console.log('Selected files for trash:', selectedFiles);
                // Implement your trash logic here
            });



            function toggleActionButtons() {
                const anyChecked = Array.from(fileCheckboxes).some(checkbox => checkbox.checked);
                downloadBtn.classList.toggle('disabled', !anyChecked);
                trashBtn.classList.toggle('disabled', !anyChecked);
            }

            function getSelectedFiles() {
                return Array.from(fileCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.getAttribute('data-path'));
            }

            function selectedFilesProcess() {
                const selectedFiles = getSelectedFiles();
                const downloadFilesInput = document.getElementById('download_files');
                const trashFilesInput = document.getElementById('trash_files');
                downloadFilesInput.value = selectedFiles.join(',');
                trashFilesInput.value = selectedFiles.join(',');
            }
        });
    </script>
</x-app-layout>
