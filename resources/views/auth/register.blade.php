@extends('layouts.guest')

@section('content')
    <ul id="successMessages" class="hidden bg-green-100 text-green-700 p-4 rounded mb-4"></ul>
    <ul id="errorMessages" class="hidden bg-red-100 text-red-700 p-4 rounded mb-4"></ul>

    <form id="registerForm" class="space-y-6">
        <div>
            <x-input-label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }}</x-input-label>
            <x-text-input type="text" id="name" name="name" required autofocus
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <div>
            <x-input-label for="folder"
                class="block text-sm font-medium text-gray-700">{{ __('Folder') }}</x-input-label>
            <x-text-input type="text" id="folder" name="folder" required pattern="^[a-zA-Z0-9_]*$"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <div>
            <x-input-label for="email"
                class="block text-sm font-medium text-gray-700">{{ __('Email') }}</x-input-label>
            <x-text-input type="email" id="email" name="email" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <div>
            <x-input-label for="password"
                class="block text-sm font-medium text-gray-700">{{ __('Password') }}</x-input-label>
            <x-text-input type="password" id="password" name="password" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <div>
            <x-input-label for="password_confirmation"
                class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</x-input-label>
            <x-text-input type="password" id="password_confirmation" name="password_confirmation" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
        </div>

        <x-primary-button type="submit"
            class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ __('Register') }}
        </x-primary-button>
    </form>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $('#registerForm').on('submit', function(event) {
            event.preventDefault();

            // Collect form data
            const formData = {
                name: $('#name').val(),
                email: $('#email').val(),
                password: $('#password').val(),
                password_confirmation: $('#password_confirmation').val(),
                folder: $('#folder').val()
            };

            // Array of functions to execute in order
            let runFuncs = [
                addApache, addPhpFpm, addPermissions, createPhpIni, createIndexPhp, reloadServices, loginUser
            ];

            // Function to run all functions in order
            function runNextFunction(index) {
                if (index < runFuncs.length) {
                    runFuncs[index](formData, function() {
                        runNextFunction(index + 1); // Call the next function after success
                    });
                }
            }

            // Step 1: Register user in the system
            $.ajax({
                url: '{{ route('register.createUser') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: formData,
                success: function(data) {
                    if (data.status) {
                        showSuccess(data.message || 'User registered successfully');
                        runNextFunction(0); // Start running functions from the array
                    } else {
                        showError(data.message || 'Registration failed at step 1');
                    }
                },
                error: function(xhr) {
                    showError(xhr.responseJSON.message || 'An error occurred during registration');
                }
            });

            // Function to configure Apache
            function addApache(formData, callback) {
                $.ajax({
                    url: '{{ route('register.addApache') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        if (data.status) {
                            showSuccess(data.message || 'Apache configured successfully');
                            callback();
                        } else {
                            showError(data.message || 'Failed to configure Apache');
                        }
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON.message ||
                            'An error occurred while configuring Apache');
                    }
                });
            }

            // Function to configure PHP-FPM
            function addPhpFpm(formData, callback) {
                $.ajax({
                    url: '{{ route('register.addPhpFpm') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess('PHP-FPM added and configuration restarted');
                        callback();
                    },
                    error: function(xhr) {
                        if (xhr.status === 503) {
                            showSuccess('PHP-FPM added but encountered a 503 error');
                            addPermissions(formData, callback);
                        } else {
                            showError('An error occurred while adding PHP-FPM');
                        }
                    }
                });
            }

            // Function to add permissions
            function addPermissions(formData, callback) {
                $.ajax({
                    url: '{{ route('register.addPermissions') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess(data.message || 'Permissions added successfully');
                        callback();
                    },
                    error: function(xhr) {
                        showError('An error occurred while adding permissions');
                    }
                });
            }

            // Function to create php.ini file
            function createPhpIni(formData, callback) {
                $.ajax({
                    url: '{{ route('register.createPhpIni') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess(data.message || 'php.ini file created successfully');
                        callback();
                    },
                    error: function(xhr) {
                        showError('An error occurred while creating php.ini file');
                    }
                });
            }

            // Function to create index.php file
            function createIndexPhp(formData, callback) {
                $.ajax({
                    url: '{{ route('register.createIndexPhp') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess(data.message || 'index.php file created successfully');
                        callback();
                    },
                    error: function(xhr) {
                        showError('An error occurred while creating index.php file');
                    }
                });
            }

            // Function to reload services
            function reloadServices(formData, callback) {
                $.ajax({
                    url: '{{ route('register.reloadServices') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess('Services reloaded successfully');
                        callback();
                    },
                    error: function(xhr) {
                        if (xhr.status === 503) {
                            showSuccess('Services reloaded but encountered a 503 error');
                            callback();
                        } else {
                            showError('An error occurred while reloading services');
                        }
                    }
                });
            }

            // Function to login user
            function loginUser(formData, callback) {
                $.ajax({
                    url: '{{ route('register.loginUser') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess(data.message || 'User logged in successfully');
                        window.location.href = '{{ route('dashboard') }}';
                    },
                    error: function(xhr) {
                        showError('An error occurred while logging in user');
                    }
                });
            }

            // Function to display success message
            function showSuccess(message) {
                $('#successMessages').removeClass('hidden').append('<li>' + message + '</li>');
            }

            // Function to display error message
            function showError(message) {
                $('#errorMessages').removeClass('hidden').append('<li>' + message + '</li>');
            }
        });
    </script>
@endsection
