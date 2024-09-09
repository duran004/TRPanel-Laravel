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
    public function rollBackExec(string $message): void
    {
        Log::info($message);
        /**
         *  @todo: Hata durumunda işlemleri geri al
         */
        throw new Exception($message);
    }
    public function createUser(string $username, string $password)
    {
        /**
         *  Önce kullanıcıyı oluştur
         */
        exec("sudo adduser --disabled-password --gecos '' $username", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Kullanıcı oluşturulamadı');
        }
        $this->addSuccess('createUserCommand', '✔ User created');
        /**
         *  Kullanıcıya şifre ayarla
         */
        exec("echo '$username:$password' | sudo chpasswd", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Şifre ayarlanamadı ');
        }
        $this->addSuccess('setPasswordCommand', '✔ Password set');
        /**
         *  Kullanıcıya ev dizinini ayarla
         */
        exec("sudo usermod -d /home/$username -m $username", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Ev dizini ayarlanamadı ');
        }
        $this->addSuccess('setHomeDirectoryCommand', '✔ Home directory set');
        /**
         *  Kullanıcıya geçiş yap
         */
        exec("sudo su - $username", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Kullanıcıya geçiş yapılamadı');
        }
        $this->addSuccess('loginUserCommand', '✔ User logged in');
        /**
         *  Kullanıcıya ev dizinini ayarla
         */
        exec("sudo chmod 750 /home/$username", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Ev dizini izinleri ayarlanamadı');
        }
        $this->addSuccess('chmd', "✔ chmod 750 /home/$username");
    }

    public function addPermission(string $username)
    {
        $publicHtmlDir = "/home/$username/public_html";
        $homeDir = "/home/$username";

        // Apache'nin erişimi için home dizin izinlerini ayarlayın
        exec("sudo chmod 750 $homeDir", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('home dizini izinleri ayarlanamadı');
        }
        $this->addSuccess('chmod_home', "✔ chmod 750 $homeDir");

        // public_html dizini oluşturma ve izin ayarlama
        if (!is_dir($publicHtmlDir)) {
            mkdir($publicHtmlDir, 0750, true);
            file_put_contents("$publicHtmlDir/index.php", "<?php\n phpinfo();");
        }

        exec("sudo chown $username:www-data $publicHtmlDir", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini chown edilemedi');
        }
        $this->addSuccess('chown_public_html', "✔ chown $username:www-data $publicHtmlDir");

        // PHP-FPM soket dosyasına sahiplik ve izin ayarlama
        exec("sudo chown $username:www-data /run/php/php8.3-fpm-$username.sock", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm soketi chown edilemedi');
        }
        exec("sudo chmod 0660 /run/php/php8.3-fpm-$username.sock", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm soketi chmod edilemedi');
        }

        // public_html dizinine Apache'nin erişimi için izinleri ayarlayın
        exec("sudo chmod 750 $publicHtmlDir", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('public_html dizini izinleri ayarlanamadı');
        }
        $this->addSuccess('chmod_public_html', "✔ chmod 750 $publicHtmlDir");

        // Kullanıcıyı www-data grubuna ekle
        exec("sudo usermod -a -G $username www-data", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Kullanıcı www-data grubuna eklenemedi: ' . implode("\n", $output));
        }

        // Apache varsayılan sitesini devre dışı bırak
        exec('sudo a2dissite 000-default.conf', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('000-default.conf kaldırılamadı');
        }
        $this->addSuccess('a2dissite', '✔ a2dissite 000-default.conf');

        // /home dizini için son düzenlemeler
        exec("sudo chmod 755 /home/$username", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('home dizini izinleri ayarlanamadı');
        }
        $this->addSuccess('chmod_home', '✔ chmod 755 /home/$username');
    }

    public function reloadSystem()
    {
        exec('sudo systemctl reload php8.3-fpm', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload PHP-FPM: ' . implode("\n", $output));
        }

        // Apache'yi yeniden başlat
        exec('sudo systemctl reload apache2', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('Failed to reload Apache: ' . implode("\n", $output));
        }
    }
    public function addPhpFpm(string $username)
    {
        //php-fpm config dosyasına kullanıcı ekleme
        $phpFpmConfigContent = file_get_contents(base_path('server/php/php-fpm.conf'));
        $phpFpmConfigContent = str_replace('TRPANEL_USER', $username, $phpFpmConfigContent);
        $phpFpmConfigFile = '/etc/php/8.3/fpm/pool.d/' . $username . '.conf';
        //apache2 config dosyasına kullanıcı ekleme
        $apache2ConfigContent = file_get_contents(base_path('server/apache/apache.conf'));
        $apache2ConfigContent = str_replace('TRPANEL_USER', $username, $apache2ConfigContent);
        $DOMAIN_NAME = 'localhost'; //$_SERVER['HTTP_HOST'];
        $apache2ConfigContent = str_replace('DOMAIN_NAME', $DOMAIN_NAME, $apache2ConfigContent);
        $apache2ConfigFile = '/etc/apache2/sites-available/' . $username . '.conf';
        /**
         *  php-fpm config dosyasına kullanıcı ekle
         */
        file_put_contents($phpFpmConfigFile, $phpFpmConfigContent);
        exec('sudo systemctl restart php8.3-fpm', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('php-fpm yeniden başlatılamadı: ' . implode("\n", $output));
        }
        $this->addSuccess('phpFpmConfigFile', '✔ php-fpm config file created');
        /**
         *  apache2 config dosyasına kullanıcı ekle
         */
        file_put_contents($apache2ConfigFile, $apache2ConfigContent);
        exec('sudo a2ensite ' . $username . '.conf', $output, $returnVar);
        if ($returnVar !== 0) {
            $this->rollBackExec('apache2 config dosyası oluşturulamadı: ' . implode("\n", $output));
        }
    }

    public function createPhpIni(string $username)
    {
        $phpIniContent = file_get_contents(base_path('server/php/php.ini'));
        $phpIniFolder = '/etc/php/8.3/fpm/' . $username;
        if (!is_dir($phpIniFolder)) {
            mkdir($phpIniFolder, 0755, true);
        }
        $phpIniFile = $phpIniFolder . '/php.ini';
        $phpIniContent = str_replace('TRPANEL_USER', $username, $phpIniContent);
        file_put_contents($phpIniFile, $phpIniContent);
        $this->addSuccess('phpIniFile', '✔ php.ini file created');
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
