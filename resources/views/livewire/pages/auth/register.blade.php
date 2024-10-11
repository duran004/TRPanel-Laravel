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
        Log::info($message);
    }

    public function getSuccessMessages(): array
    {
        return $this->successes;
    }

    public function rollBackExec(string $message, $output = null): void
    {
        Log::error('ERROR: ' . $message);
        if ($output) {
            Log::error('ERROR Command Output: ' . implode("\n", $output));
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
    public function homedirPermission(string $username, string $who = 'trpanel')
    {
        if ($who == 'trpanel') {
            exec("sudo chown -R trpanel:www-data /home/$username 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->rollBackExec('Geçici sahiplik ayarlanamadı', $output);
            }
            $this->addSuccess('chown', 'Geçici sahiplik ayarlandı');
        } else {
            exec("sudo chown -R $username:$username /home/$username 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->rollBackExec('Orijinal sahiplik ayarlanamadı', $output);
            }
            $this->addSuccess('chown', 'Orijinal sahiplik ayarlandı');
        }
    }
    public function addPermission(string $username)
    {
        // Kullanıcıya ait home dizinini oluşturma ve sahiplik ayarlarını yapma
        exec("sudo mkdir -p /home/$username 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Home dizini oluşturulamadı', $output);
        }

        $this->homedirPermission($username, 'trpanel');

        // public_html dizinini oluşturma ve izin ayarlama
        File::makeDirectory("/home/$username/public_html", 0755, true);
        file_put_contents(
            "/home/$username/public_html/index.php",
            "<?php\n 
            echo 'Hello, $username!';\n
            phpinfo();",
        );

        // PHP ve extensions dizinlerini oluşturma ve izin ayarlama
        foreach (["/home/$username/php", "/home/$username/php/extensions"] as $dir) {
            if (!is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        // Sahipliği kullanıcıya geri verme
        $this->homedirPermission($username, 'user');
        // public_html dizinini kullanıcıya atama
        exec("sudo chown -R $username:www-data /home/$username/public_html 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini sahipliği ayarlanamadı', $output);
        }
        exec("sudo chmod 755 /home/$username/public_html 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini izinleri ayarlanamadı', $output);
        }
        $this->addSuccess('chmod_public_html', "✔ chmod 755 /home/$username/public_html");
        //@TRPANEL
        $this->homedirPermission($username, 'trpanel');
        // PHP-FPM soket dosyasına sahiplik ve izin ayarlama
        $fpmSocket = "/run/php/php8.3-fpm-$username.sock";
        if (file_exists($fpmSocket)) {
            exec("sudo chown $username:www-data $fpmSocket 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->rollBackExec('php-fpm soketi sahipliği ayarlanamadı', $output);
            }
            exec("sudo chmod 0660 $fpmSocket 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->rollBackExec('php-fpm soketi izinleri ayarlanamadı', $output);
            }
        } else {
            $this->rollBackExec('php-fpm soketi bulunamadı: ' . $fpmSocket);
        }

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
    }

    public function reloadSystem()
    {
        // PHP-FPM ve Apache yeniden yükle
        exec('sudo systemctl reload php8.3-fpm 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload PHP-FPM', $output);
        } else {
            $this->addSuccess('reloadPhpFpm', '✔ PHP-FPM reloaded');
        }

        exec('sudo systemctl reload apache2 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload Apache', $output);
        } else {
            $this->addSuccess('reloadSystem', '✔ PHP-FPM and Apache reloaded');
        }
    }

    public function addPhpFpm(string $username)
    {
        exec('sudo whoami', $output, $returnVar);

        if ($returnVar === 0) {
            Log::info('Current user: ' . $output[0]);
        }
        $envVars = shell_exec('printenv');
        Log::info('Env vars ' . $envVars);
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
        $this->addSuccess('phpFpmConfigFile', '✔ php-fpm config file created ' . $phpFpmConfigFile);

        // apache2 config dosyasına kullanıcı ekle
        file_put_contents($apache2ConfigFile, $apache2ConfigContent);
        $this->addSuccess('apache2ConfigFile', '✔ apache2 config file created ' . $apache2ConfigFile);

        exec('sudo a2ensite ' . $username . '.conf 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('apache2 config dosyası oluşturulamadı: ' . implode("\n", $output));
        }
        $this->addSuccess('a2ensite', '✔ a2ensite ' . $username . '.conf');

        $this->reloadSystem();
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

            $this->addSuccess('loginCommand', '✔ Logged in to trpanel');
            $this->createUser($username, $password);
            $this->addPhpFpm($username);
            $this->addPermission($username);
            $this->createPhpIni($username);
            $this->reloadSystem();
            $this->addSuccess('register', 'User created successfully');
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
