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
        $this->dispatch('successAdded', ['message' => $message]);
    }

    public function getSuccessMessages(): array
    {
        return $this->successes;
    }

    public function rollBackExec(string $message, $output = null): void
    {
        Log::info($message);
        if ($output) {
            Log::info('Command Output: ' . implode("\n", $output));
        }
        throw new Exception($message);
    }

    public function createUser(string $username, string $password)
    {
        // Kullanıcı oluşturma
        exec("sudo adduser --disabled-password --gecos '' $username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Kullanıcı oluşturulamadı', $output);
        }
        $this->addSuccess('createUserCommand', '✔ User created');

        // Şifre ayarlama
        exec("echo '$username:$password' | sudo chpasswd 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Şifre ayarlanamadı', $output);
        }
        $this->addSuccess('setPasswordCommand', '✔ Password set');

        // Ev dizini ayarlama
        exec("sudo usermod -d /home/$username -m $username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Ev dizini ayarlanamadı', $output);
        }
        $this->addSuccess('setHomeDirectoryCommand', '✔ Home directory set');

        // İzinleri ayarlama
        exec("sudo chmod 750 /home/$username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Ev dizini izinleri ayarlanamadı', $output);
        }
        $this->addSuccess('chmod', "✔ chmod 750 /home/$username");
    }

    public function addPermission(string $username)
    {
        $publicHtmlDir = "/home/$username/public_html";
        $homeDir = "/home/$username";
        $phpDir = "/home/$username/php";
        $phpExtDir = "/home/$username/php/extensions";
        // home dizin izinlerini ayarlama
        exec("sudo chmod 750 $homeDir 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('home dizini izinleri ayarlanamadı', $output);
        }
        $this->addSuccess('chmod_home', "✔ chmod 750 $homeDir");

        // public_html oluşturma ve izin ayarlama
        if (!is_dir($publicHtmlDir)) {
            mkdir($publicHtmlDir, 0750, true);
            file_put_contents("$publicHtmlDir/index.php", "<?php\n phpinfo();");
        }

        exec("sudo chown $username:www-data $publicHtmlDir 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini chown edilemedi', $output);
        }
        $this->addSuccess('chown_public_html', "✔ chown $username:www-data $publicHtmlDir");

        // php dizin oluşturma ve izin ayarlama
        if (!is_dir($phpDir)) {
            mkdir($phpDir, 0750, true);
        }
        exec("sudo chown $username:www-data $phpDir 2>&1", $output, $returnVar);

        if ($returnVar !== 0) {
            $this->rollBackExec('php dizini chown edilemedi', $output);
        }
        $this->addSuccess('chown_php', "✔ chown $username:www-data $phpDir");

        // php/extensions dizin oluşturma ve izin ayarlama
        if (!is_dir($phpExtDir)) {
            mkdir($phpExtDir, 0750, true);
        }
        exec("sudo chown $username:www-data $phpExtDir 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php/extensions dizini chown edilemedi', $output);
        }
        $this->addSuccess('chown_php_extensions', "✔ chown $username:www-data $phpExtDir");

        // PHP-FPM soket dosyasına sahiplik ve izin ayarlama
        exec("sudo chown $username:www-data /run/php/php8.3-fpm-$username.sock 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm soketi chown edilemedi', $output);
        }
        exec("sudo chmod 0660 /run/php/php8.3-fpm-$username.sock 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm soketi chmod edilemedi', $output);
        }

        // public_html izinlerini ayarlama
        exec("sudo chmod 750 $publicHtmlDir 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini izinleri ayarlanamadı', $output);
        }
        $this->addSuccess('chmod_public_html', "✔ chmod 750 $publicHtmlDir");

        // Kullanıcıyı www-data grubuna ekleme
        exec("sudo usermod -a -G www-data $username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Kullanıcı www-data grubuna eklenemedi', $output);
        }

        // Apache varsayılan sitesini devre dışı bırakma
        exec('sudo a2dissite 000-default.conf 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('000-default.conf kaldırılamadı', $output);
        }
        $this->addSuccess('a2dissite', '✔ a2dissite 000-default.conf');

        // Son olarak ev dizini izinlerini ayarlama
        exec("sudo chmod 755 /home/$username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('home dizini izinleri ayarlanamadı', $output);
        }
        $this->addSuccess('chmod_home', '✔ chmod 755 /home/$username');
    }

    public function reloadSystem()
    {
        // PHP-FPM ve Apache yeniden yükle
        exec('sudo systemctl reload php8.3-fpm 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload PHP-FPM', $output);
        }

        exec('sudo systemctl reload apache2 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload Apache', $output);
        }
    }

    public function addPhpFpm(string $username)
    {
        // php-fpm config dosyasına kullanıcı ekleme
        $phpFpmConfigContent = file_get_contents(base_path('server/php/php-fpm.conf'));
        $phpFpmConfigContent = str_replace('TRPANEL_USER', $username, $phpFpmConfigContent);
        $phpFpmConfigFile = '/etc/php/8.3/fpm/pool.d/' . $username . '.conf';

        // apache2 config dosyasına kullanıcı ekleme
        $apache2ConfigContent = file_get_contents(base_path('server/apache/apache.conf'));
        $apache2ConfigContent = str_replace('TRPANEL_USER', $username, $apache2ConfigContent);
        $DOMAIN_NAME = 'localhost';
        $apache2ConfigContent = str_replace('DOMAIN_NAME', $DOMAIN_NAME, $apache2ConfigContent);
        $apache2ConfigFile = '/etc/apache2/sites-available/' . $username . '.conf';

        // php-fpm config dosyasına kullanıcı ekle
        file_put_contents($phpFpmConfigFile, $phpFpmConfigContent);
        exec('sudo systemctl restart php8.3-fpm 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm yeniden başlatılamadı: ' . implode("\n", $output));
        }
        $this->addSuccess('phpFpmConfigFile', '✔ php-fpm config file created');

        // apache2 config dosyasına kullanıcı ekle
        file_put_contents($apache2ConfigFile, $apache2ConfigContent);
        exec('sudo a2ensite ' . $username . '.conf 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('apache2 config dosyası oluşturulamadı: ' . implode("\n", $output));
        }
    }

    public function createPhpIni(string $username)
    {
        $phpIniContent = file_get_contents(base_path('server/php/php.ini'));
        $phpIniFolder = "/home/$username/public_html";
        if (!is_dir($phpIniFolder)) {
            mkdir($phpIniFolder, 0755, true);
        }
        $phpIniFile = $phpIniFolder . '/.user.ini';
        $phpIniContent = str_replace('TRPANEL_USER', $username, $phpIniContent);
        file_put_contents($phpIniFile, $phpIniContent);
        $this->addSuccess('phpIniFile', '✔ php.ini file created');
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'folder' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);
        $validated['password'] = Hash::make($validated['password']);

        try {
            $userFolder = env('LINUX_HOME') . $validated['folder'];
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $userFolder = env('WINDOWS_HOME') . $validated['folder'];
                if (!file_exists($userFolder)) {
                    $oldUmask = umask(0);
                    mkdir($userFolder, 0777, true);
                    umask($oldUmask);
                } else {
                    $this->addError('folder', 'Folder already exists');
                    return;
                }
            } else {
                $username = $validated['folder'];
                $password = $validated['password'];
                $this->createUser($username, $password);
                $this->addPhpFpm($username);
                $this->addPermission($username);
                $this->createPhpIni($username);
                $this->reloadSystem();
                $this->addSuccess('apache2ConfigFile', '✔ apache2 config file created');
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $user = User::create($validated);
        event(new Registered($user));
        Auth::login($user);
        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
};
?>
<div>
    <div>
        <ul id="successMessages" class="alert alert-success">
            <!-- Başarı mesajları buraya eklenecek -->
        </ul>
    </div>
    <script>
        document.addEventListener('livewire:init', function() {
            console.log('Livewire is ready!');
            const successList = document.getElementById('successMessages');

            Livewire.on('successAdded', function(event) {
                console.log("Success added: " + event.message);
                const message = event.message;

                // Yeni bir <li> elemanı oluştur
                const listItem = document.createElement('li');
                listItem.textContent = message;

                // Listeye mesajı ekle
                successList.appendChild(listItem);

                // Sayfa sonunda mesajı göster
                window.scrollTo(0, document.body.scrollHeight);
            });
        });
    </script>
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
