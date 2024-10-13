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

            // Array of actions with corresponding route and success messages
            let runFuncs = [{
                    route: '{{ route('register.addApache') }}',
                    message: 'Apache configured successfully'
                },
                {
                    route: '{{ route('register.addPhpFpm') }}',
                    message: 'PHP-FPM added and configured successfully'
                },
                {
                    route: '{{ route('register.addPermissions') }}',
                    message: 'Permissions added successfully'
                },
                {
                    route: '{{ route('register.createPhpIni') }}',
                    message: 'php.ini file created successfully'
                },
                {
                    route: '{{ route('register.createIndexPhp') }}',
                    message: 'index.php file created successfully'
                },
                {
                    route: '{{ route('register.reloadServices') }}',
                    message: 'Services reloaded successfully'
                },
                {
                    route: '{{ route('register.loginUser') }}',
                    message: 'User logged in successfully'
                }
            ];

            // Function to run all AJAX calls in order
            function runNextFunction(index) {
                if (index < runFuncs.length) {
                    ajaxRequest(runFuncs[index], function() {
                        runNextFunction(index + 1); // Call the next function after success
                    });
                }
            }

            // Start process with user registration
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

            // General AJAX request function
            function ajaxRequest(action, callback) {
                $.ajax({
                    url: action.route,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    success: function(data) {
                        showSuccess(data.message || action.message);
                        callback();
                    },
                    error: function(xhr) {
                        showError('An error occurred while performing action: ' + action.message);
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
