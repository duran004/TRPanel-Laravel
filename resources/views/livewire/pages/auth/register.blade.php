<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $folder = '';
    public array $successes = [];

    public function addSuccess(string $key, string $message): void
    {
        $this->successes[$key] = $message;
    }
    public function getSuccessMessages(): array
    {
        return $this->successMessages;
    }
    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'folder' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);
        $validated['password'] = Hash::make($validated['password']);
        // eğer linux ise home altına windows ise C:/temp/ altına klasör oluştur
        try {
            $userFolder = env('LINUX_HOME') . $validated['folder'];
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $userFolder = env('WINDOWS_HOME') . $validated['folder'];
                if (!file_exists($userFolder)) {
                    $oldUmask = umask(0); // Geçici olarak dosya izinlerini değiştir
                    mkdir($userFolder, 0777, true);
                    umask($oldUmask); // Eski dosya izinlerini geri yükle
                } else {
                    $this->addError('folder', 'Folder already exists');
                    return;
                }
            } else {
                //linux
                $username = $validated['folder'];
                $password = $validated['password'];
                $createUserCommand = "sudo adduser --disabled-password --gecos '' $username";
                $setPasswordCommand = "echo '$username:$password' | sudo chpasswd";
                // Kullanıcıyı kendi grubuna alma ve ev dizinini ayarlama komutu
                $setHomeDirectoryCommand = "sudo usermod -d /home/$username -m $username";
                // Kullanıcıya geçiş yapma komutu
                $loginUserCommand = "sudo su - $username";
                // Kullanıcıya ev dizinini ayarlama
                $chmd = "sudo chmod 750 /home/$username";
                //php-fpm config dosyasına kullanıcı ekleme
                $phpFpmConfigContent = file_get_contents(base_path('server/php/php-fpm.conf'));
                $phpFpmConfigContent = str_replace('TRPANEL_USER', $username, $phpFpmConfigContent);
                $phpFpmConfigFile = '/etc/php/8.3/fpm/pool.d/' . $username . '.conf';
                /**
                 *  Önce kullanıcıyı oluştur
                 */
                exec($createUserCommand, $output, $returnVar);
                if ($returnVar !== 0) {
                    die('Kullanıcı oluşturulamadı: ' . implode("\n", $output));
                }
                $this->addSuccess('createUserCommand', '✔ User created');
                /**
                 *  Kullanıcıya şifre ayarla
                 */
                exec($setPasswordCommand, $output, $returnVar);
                if ($returnVar !== 0) {
                    die('Şifre ayarlanamadı: ' . implode("\n", $output));
                }
                $this->addSuccess('setPasswordCommand', '✔ Password set');
                /**
                 *  Kullanıcıya ev dizinini ayarla
                 */
                exec($setHomeDirectoryCommand, $output, $returnVar);
                if ($returnVar !== 0) {
                    die('Ev dizini ayarlanamadı: ' . implode("\n", $output));
                }
                $this->addSuccess('setHomeDirectoryCommand', '✔ Home directory set');
                /**
                 *  Kullanıcıya geçiş yap
                 */
                exec($loginUserCommand, $output, $returnVar);
                if ($returnVar !== 0) {
                    die('Kullanıcıya geçiş yapılamadı: ' . implode("\n", $output));
                }
                $this->addSuccess('loginUserCommand', '✔ User logged in');
                /**
                 *  Kullanıcıya ev dizinini ayarla
                 */
                exec($chmd, $output, $returnVar);
                if ($returnVar !== 0) {
                    die('Kullanıcıya geçiş yapılamadı: ' . implode("\n", $output));
                }
                $this->addSuccess('chmd', "✔ chmod 750 /home/$username");
                /**
                 *  php-fpm config dosyasına kullanıcı ekle
                 */
                file_put_contents($phpFpmConfigFile, $phpFpmConfigContent);
                exec('sudo systemctl restart php8.3-fpm', $output, $returnVar);
                if ($returnVar !== 0) {
                    die('php-fpm restart edilemedi: ' . implode("\n", $output));
                }
                $this->addSuccess('phpFpmConfigFile', '✔ php-fpm config file created');
            }
        } catch (Exception $e) {
            $this->addError('folder', 'Folder could not be created ' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile() . ' ' . $userFolder);
            return;
        }

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required
                autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Folder -->
        <div class="mt-4">
            <x-input-label for="folder" :value="__('Folder')" />
            <x-input-label for="folder" :value="__('Your main folder name must be without spaces and special characters')" />
            <x-text-input wire:model="folder" id="folder" class="block mt-1 w-full" type="text" name="folder"
                required autocomplete="folder" pattern="^[a-zA-Z0-9_]*$" />
            <x-input-error :messages="$errors->get('folder')" class="mt-2" />
            <x-input-success :messages="$successes" class="mt-2" />

        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email"
                required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password"
                required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                type="password" name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
