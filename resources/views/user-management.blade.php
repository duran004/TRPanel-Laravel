@extends('layouts.guest')

@section('content')
    <div class="max-w-lg mx-auto my-10 p-8 bg-white rounded-lg shadow-lg">
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
    </div>

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
                        if (data.status) {
                            showSuccess('Registration successful');
                        } else {
                            showError(data.message || 'Registration failed at step 3');
                        }
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON.message || 'An error occurred during registration');
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
