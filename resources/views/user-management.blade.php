@extends('layouts.guest')

@section('content')
    <ul id="successMessages" class="alert alert-success d-none"></ul>
    <ul id="errorMessages" class="alert alert-danger d-none"></ul>

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
                url: '{{ route('register.createUser') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: formData,
                success: function(data) {
                    if (data.status) {
                        // Step 2: Configure PHP-FPM and Apache
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
                $('#successMessages').removeClass('d-none');
                const message_el = $('li').text(message);
                $('#successMessages').append(message_el);
            }

            // Function to display error message
            function showError(message) {
                $('#errorMessages').removeClass('d-none');
                const message_el = $('li').text(message);
                $('#errorMessages').append(message_el);
            }
        });
    </script>
@endsection
