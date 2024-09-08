<div id="context-menu" class="hidden bg-white shadow-md rounded-md py-2">
    <a href="#" data-type="file" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200" data-action="Open">
        <i class="fas fa-eye"></i> {{ __('Edit') }}</a>
    <a href="#" data-type="all" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200"
        data-action="Download"><i class="fas fa-download"></i>{{ __('Download') }}</a>
    <a href="#" data-type="all" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200"
        data-action="Reload"><i class="fas fa-sync"></i> {{ __('Reload') }}</a>
    <a href="#" data-type="all" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200"
        data-action="Rename"><i class="fas fa-edit"></i> {{ __('Rename') }}</a>
    <a href="#" data-type="zip" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200"
        data-action="Extract"><i class="fas fa-file-archive"></i> {{ __('Extract') }}</a>
    {{-- <a href="#" data-type="zip,dir,file" class="block px-4 py-1 text-sm text-gray-800 hover:bg-gray-200"
        data-action="Compress"><i class="fas fa-compress"></i> {{ __('Compress') }}</a> --}}
</div>

<script>
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
                const fileType = this.getAttribute('data-type');
                unselectAllFiles();
                selectRow(this);
                showContextMenu(event, filePath, fileType);
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
            toggleButtons();
        }

        document.addEventListener('click', function() {
            contextMenu.classList.add('hidden');
        });

        function showContextMenu(event, filePath, fileType) {
            contextMenu.style.top = `${event.pageY}px`;
            contextMenu.style.left = `${event.pageX}px`;
            contextMenu.classList.remove('hidden');
            // dir ise open ve delete olmamalı zip ise extract olmalı
            contextMenu.querySelectorAll('[data-type]').forEach(function(menuItem) {
                menuItem.classList.add('hidden');
                const types = menuItem.getAttribute('data-type').split(',');
                if (types.includes(fileType) || types.includes('all')) {
                    menuItem.classList.remove('hidden');
                }
            });

            console.log('File Path:', filePath);
            console.log('File Type:', fileType);

            contextMenu.querySelectorAll('a').forEach(function(menuItem) {
                menuItem.onclick = function() {
                    handleMenuItemClick(this.getAttribute('data-action'), filePath);
                };
            });
        }

        function handleMenuItemClick(action, filePath) {
            console.log(`Action: ${action}, File Path: ${filePath}`);
            contextMenu.classList.add('hidden');
            switch (action) {
                case 'Open':
                    const normalizedPath = normalizePath(filePath);
                    console.log('Normalized Path:', normalizedPath);
                    $.ajax({
                        url: '/filemanager/file/preview',
                        type: 'GET',
                        data: {
                            file: normalizedPath
                        },
                        success: function(response) {
                            $.dialog({
                                title: 'Preview',
                                content: response.message,
                                columnClass: 'medium',
                                closeIcon: true,
                                backgroundDismiss: true,
                                onOpenBefore: function() {
                                    $('.jconfirm-row').addClass(
                                        'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                    );
                                    $('.jconfirm-holder').addClass(
                                        'flex items-center justify-center');
                                },
                            });
                        }
                    });
                    break;
                case 'Download':
                    const downloadForm = document.querySelector('#download-form');
                    console.warn('Download Form:', downloadForm);
                    const downloadInput = downloadForm.querySelector('input[name="_files"]');
                    console.warn('Download Input:', downloadInput);
                    downloadInput.value = filePath;
                    downloadForm.submit();
                    break;
                case 'Reload':
                    window.location.reload();
                    break;
                case 'Rename':
                    console.log('File Path:', filePath);
                    const fileName = filePath.split('//').pop();
                    console.log('File Name:', fileName);

                    $.alert({
                        title: 'Rename File',
                        content: `<input type="text" class="w-full my-2" value="${fileName}" id="new-name">`,
                        onOpenBefore: function() {
                            $('.jconfirm-row').addClass(
                                'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                            );
                            $('.jconfirm-holder').addClass(
                                'flex items-center justify-center');
                        },
                        buttons: {
                            confirm: {
                                text: 'Rename',
                                btnClass: 'bg-blue-500 text-white',
                                action: function() {
                                    const newName = document.getElementById('new-name').value;
                                    $.ajax({
                                        url: '{{ route('filemanager.file.rename') }}',
                                        type: 'POST',
                                        data: {
                                            file: filePath,
                                            new_name: newName,
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function(response) {
                                            if (response.status) {
                                                window.location.reload();
                                            } else {
                                                $.alert({
                                                    title: 'Error',
                                                    content: response.message,
                                                    type: 'red',
                                                    onOpenBefore: function() {
                                                        $('.jconfirm-row').addClass(
                                                            'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                        );
                                                        $('.jconfirm-holder')
                                                            .addClass(
                                                                'flex items-center justify-center'
                                                            );
                                                    },
                                                });
                                            }
                                        },
                                        error: function(response) {
                                            $.alert({
                                                title: 'Error',
                                                content: response.responseJSON.message,
                                                type: 'red',
                                                onOpenBefore: function() {
                                                    $('.jconfirm-row').addClass(
                                                        'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                    );
                                                    $('.jconfirm-holder').addClass(
                                                        'flex items-center justify-center'
                                                    );
                                                },
                                            });
                                        }
                                    });
                                }
                            },
                            cancel: {
                                text: 'Cancel',
                                btnClass: 'bg-red-500 text-white',
                            }
                        },
                    });

                    break;
                case 'Extract':
                    const extractPath = filePath.split('//').slice(0, -1).join('//');
                    console.warn(filePath);
                    $.alert({
                        title: '{{ __('Extract Path') }}',
                        content: `{{ __('It can override existing files. Are you sure to extract?') }}<br><br>
                        <input type="text" class="form-control w-full" value="${extractPath}" name="path" id="extract-path">`,
                        onOpenBefore: function() {
                            $('.jconfirm-row').addClass(
                                'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                            );
                            $('.jconfirm-holder').addClass(
                                'flex items-center justify-center');
                        },
                        buttons: {
                            confirm: {
                                text: 'Extract',
                                btnClass: 'bg-blue-500 text-white',
                                action: function() {
                                    const extractPath = document.getElementById('extract-path').value;
                                    $.ajax({
                                        url: '{{ route('filemanager.file.extract') }}',
                                        type: 'POST',
                                        data: {
                                            path: extractPath,
                                            file: filePath,
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function(response) {
                                            if (response.status) {
                                                $.alert({
                                                    title: 'Success',
                                                    content: response.message,
                                                    type: 'green',
                                                    onOpenBefore: function() {
                                                        $('.jconfirm-row').addClass(
                                                            'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                        );
                                                        $('.jconfirm-holder')
                                                            .addClass(
                                                                'flex items-center justify-center'
                                                            );
                                                    },
                                                    buttons: {
                                                        confirm: {
                                                            text: 'OK',
                                                            btnClass: 'bg-green-500 text-white',
                                                            action: function() {
                                                                window.location
                                                                    .reload();
                                                            }
                                                        }
                                                    }
                                                });
                                                // window.location.reload();
                                            } else {
                                                $.alert({
                                                    title: 'Error',
                                                    content: response.message,
                                                    type: 'red',
                                                    onOpenBefore: function() {
                                                        $('.jconfirm-row').addClass(
                                                            'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                        );
                                                        $('.jconfirm-holder')
                                                            .addClass(
                                                                'flex items-center justify-center'
                                                            );
                                                    },
                                                });
                                            }
                                        },
                                        error: function(response) {
                                            $.alert({
                                                title: 'Error',
                                                content: response.responseJSON.message,
                                                type: 'red',
                                                onOpenBefore: function() {
                                                    $('.jconfirm-row').addClass(
                                                        'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                    );
                                                    $('.jconfirm-holder').addClass(
                                                        'flex items-center justify-center'
                                                    );
                                                },
                                            });
                                        }
                                    });
                                }
                            },
                            cancel: {
                                text: 'Cancel',
                                btnClass: 'bg-red-500 text-white',
                            }
                        },
                    });
                    break;

                    const selectedFiles = getSelectedFiles();
                    const compressPath = filePath.split('//').slice(0, -1).join('//');
                    $.alert({
                        title: '{{ __('Compress File Path') }}',
                        content: `<input type="text" class="form-control w-full" value="${compressPath}" name="path" id="compress-path">`,
                        onOpenBefore: function() {
                            $('.jconfirm-row').addClass(
                                'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                            );
                            $('.jconfirm-holder').addClass(
                                'flex items-center justify-center');
                        },
                        buttons: {
                            confirm: {
                                text: 'Compress',
                                btnClass: 'bg-blue-500 text-white',
                                action: function() {
                                    const compressPath = document.getElementById('compress-path').value;
                                    $.ajax({
                                        url: '{{ route('filemanager.file.compress') }}',
                                        type: 'POST',
                                        data: {
                                            path: compressPath,
                                            files: selectedFiles,
                                            _token: '{{ csrf_token() }}'
                                        },
                                        success: function(response) {
                                            if (response.status) {
                                                $.alert({
                                                    title: 'Success',
                                                    content: response.message,
                                                    type: 'green',
                                                    onOpenBefore: function() {
                                                        $('.jconfirm-row').addClass(
                                                            'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                        );
                                                        $('.jconfirm-holder')
                                                            .addClass(
                                                                'flex items-center justify-center'
                                                            );
                                                    },
                                                    buttons: {
                                                        confirm: {
                                                            text: 'OK',
                                                            btnClass: 'bg-green-500 text-white',
                                                            action: function() {
                                                                window.location
                                                                    .reload();
                                                            }
                                                        }
                                                    }
                                                });
                                                // window.location.reload();
                                            } else {
                                                $.alert({
                                                    title: 'Error',
                                                    content: response.message,
                                                    type: 'red',
                                                    onOpenBefore: function() {
                                                        $('.jconfirm-row').addClass(
                                                            'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                        );
                                                        $('.jconfirm-holder')
                                                            .addClass(
                                                                'flex items-center justify-center'
                                                            );
                                                    },
                                                });
                                            }
                                        },
                                        error: function(response) {
                                            $.alert({
                                                title: 'Error',
                                                content: response.responseJSON.message,
                                                type: 'red',
                                                onOpenBefore: function() {
                                                    $('.jconfirm-row').addClass(
                                                        'inset-0 flex items-center justify-center bg-[#ccc] bg-opacity-50'
                                                    );
                                                    $('.jconfirm-holder').addClass(
                                                        'flex items-center justify-center'
                                                    );
                                                },
                                            });
                                        }
                                    });
                                }
                            },
                            cancel: {
                                text: 'Cancel',
                                btnClass: 'bg-red-500 text-white',
                            }
                        },
                    });
                    break;

                default:
                    break;
            }

        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeContextMenu();
    });
</script>
