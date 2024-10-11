@extends('layouts.guest')

@section('content')
    <div id="successMessages" class="alert alert-success d-none"></div>
    <div id="errorMessages" class="alert alert-danger d-none"></div>

    <form id="registerForm">
        <div>
            <label for="name">{{ __('Name') }}</label>
            <input type="text" id="name" name="name" required autofocus>
        </div>

        <div>
            <label for="folder">{{ __('Folder') }}</label>
            <input type="text" id="folder" name="folder" required pattern="^[a-zA-Z0-9_]*$">
        </div>

        <div>
            <label for="email">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div>
            <label for="password">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div>
            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit">{{ __('Register') }}</button>
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
                url: '{{ route('register.user') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: formData,
                success: function(data) {
                    if (data.status) {
                        // Step 2: Configure PHP-FPM and Apache
                        configurePhpFpmAndApache(formData);
                    } else {
                        showError(data.message || 'Registration failed at step 1');
                    }
                },
                error: function(xhr) {
                    showError(xhr.responseJSON.message || 'An error occurred during registration');
                }
            });

            // Function to configure PHP-FPM and Apache
            function configurePhpFpmAndApache(formData) {
                $.ajax({
                    url: '{{ route('user.add.phpfpm.apache') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        folder: formData.folder
                    },
                    success: function(data) {
                        if (data.status) {
                            // Step 3: Save user to the database
                            saveUserToDatabase(formData);
                        } else {
                            showError(data.message || 'Failed to configure PHP-FPM and Apache');
                        }
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON.message ||
                            'An error occurred while configuring PHP-FPM and Apache');
                    }
                });
            }

            // Function to save user to the database
            function saveUserToDatabase(formData) {
                $.ajax({
                    url: '{{ route('register.user') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        name: formData.name,
                        email: formData.email,
                        password: formData.password,
                        password_confirmation: formData.password_confirmation
                    },
                    success: function(data) {
                        if (data.status) {
                            showSuccess(data.message);
                        } else {
                            showError(data.message || 'Failed to save user to the database');
                        }
                    },
                    error: function(xhr) {
                        showError(xhr.responseJSON.message ||
                            'An error occurred while saving user to the database');
                    }
                });
            }

            // Function to display success message
            function showSuccess(message) {
                $('#successMessages').removeClass('d-none').text(message);
            }

            // Function to display error message
            function showError(message) {
                $('#errorMessages').removeClass('d-none').text(message);
            }
        });
    </script>
@endsection
