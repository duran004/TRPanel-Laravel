@extends('layouts.app')

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

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            event.preventDefault();

            fetch('{{ route('register.user') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('password_confirmation').value,
                    folder: document.getElementById('folder').value,
                })
            }).then(response => response.json()).then(data => {
                if (data.status) {
                    document.getElementById('successMessages').classList.remove('d-none');
                    document.getElementById('successMessages').innerText = data.message;
                } else {
                    document.getElementById('errorMessages').classList.remove('d-none');
                    document.getElementById('errorMessages').innerText = data.message ||
                        'Registration failed!';
                }
            }).catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
@endsection
