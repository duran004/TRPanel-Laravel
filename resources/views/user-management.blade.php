@extends('layouts.guest')

@section('content')
    <ul id="successMessages" class="hidden bg-green-100 text-green-700 p-4 rounded mb-4"></ul>
    <ul id="errorMessages" class="hidden bg-red-100 text-red-700 p-4 rounded mb-4"></ul>

    <form id="registerForm" class="space-y-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
            <input type="text" id="name" name="name" required autofocus
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="folder" class="block text-sm font-medium text-gray-700">{{ __('Folder') }}</label>
            <input type="text" id="folder" name="folder" required pattern="^[a-zA-Z0-9_]*$"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="password_confirmation"
                class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <button type="submit"
            class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ __('Register') }}
        </button>
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
                                // Step 2: Configure PHP-FPM and Apache
                                showSuccess(data.message || 'User registered successfully');
                                addApache(formData);
                            } else {
                                showError(data.message || 'Registration failed at step 1');
                            }
                        },
                        error: function(xhr) {
                            showError(xhr.responseJSON.message || 'An error occurred during registration');
                        }
                    });

                    // Function to configure Apache
                    function addApache(formData) {
                        $.ajax({
                            url: '{{ route('register.addApache') }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: formData,
                            success: function(data) {
                                if (data.status) {
                                    // Step 3: Configure PHP-FPM
                                    showSuccess(data.message || 'Apache configured successfully');
                                    addPhpFpm(formData);
                                } else {
                                    showError(data.message || 'Registration failed at step 2');
                                }
                            },
                            error: function(xhr) {
                                showError(xhr.responseJSON.message || 'An error occurred during registration');
                            }
                        });
                    }

                    // Function to configure PHP-FPM
                    function addPhpFpm(formData) {
                        $.ajax({
                            url: '{{ route('register.addPhpFpm') }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: formData,
                            success: function(data) {

                            },
                            error: function(data) {
                                showSuccess('PHP-FPM added and configuration restarted');
                                //if 503
                                if (data.status == 503) {
                                    addPermissions(formData);
                                } else {
                                    showError('An error occurred while adding PHP-FPM');
                                }

                            }

                        });
                    }

                    // Function to add permissions
                    function addPermissions(formData) {
                        $.ajax({
                            url: '{{ route('register.addPermissions') }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: formData,
                            success: function(data) {
                                showSuccess(data.message || 'Permissions added successfully');
                                createPhpIni(formData);
                            },
                            error: function(data) {
                                showError('An error occurred while adding permissions');
                            }
                        });
                    }

                    // Function to create php.ini file
                    function createPhpIni(formData) {
                        $.ajax({
                            url: '{{ route('register.createPhpIni') }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: formData,
                            success: function(data) {
                                showSuccess(data.message || 'php.ini file created successfully');
                                reloadServices(formData);
                            },
                            error: function(data) {
                                showError('An error occurred while creating php.ini file');
                            }
                        });
                    }

                    // Function to reload services
                    function reloadServices(formData) {
                        $.ajax({
                                url: '{{ route('register.reloadServices') }}',
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                data: formData,
                                success: function(data) {},
                                error: function(data) {
                                    if (data.status == 503) {
                                        showSuccess('Services reloaded successfully');
                                        loginUser(formData);
                                    } else {
                                        showError('An error occurred while reloading services');
                                    }
                                });
                        }

                        // Function to login user
                        function loginUser(formData) {
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
                                error: function(data) {
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
