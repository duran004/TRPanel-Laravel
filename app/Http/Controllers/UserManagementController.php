<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Genel olarak komutları çalıştırma ve hata yönetimi metodu
     */
    private function executeCommand($command, $successMessage, $errorMessage)
    {
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("ERROR: $errorMessage", ['command' => $command, 'output' => $output]);
            return response()->json(['status' => false, 'message' => $errorMessage, 'details' => $output], 500);
        }

        Log::info($successMessage);
        return response()->json(['status' => true, 'message' => $successMessage], 200);
    }

    /**
     * Kullanıcı oluşturma ve gerekli yapılandırmaların tümünü yapma metodu
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
            'folder' => 'required|string|max:255',
        ]);

        $username = $validated['folder'];
        $password = $validated['password'];

        // Kullanıcı oluşturma
        $response = $this->executeCommand(
            "sudo adduser --disabled-password --gecos '' $username",
            __('Kullanıcı başarıyla oluşturuldu'),
            __('Kullanıcı oluşturulamadı')
        );

        // Check if the response is a JSON error response
        if ($response->getData()->status === false) {
            return $response;
        }

        // Şifre ayarlama
        $response = $this->executeCommand(
            "echo '$username:$password' | sudo chpasswd",
            __('Şifre başarıyla ayarlandı'),
            __('Şifre ayarlanamadı')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        // Ev dizini ayarlama
        $response = $this->executeCommand(
            "sudo usermod -d /home/$username -m $username",
            __('Ev dizini başarıyla ayarlandı'),
            __('Ev dizini ayarlanamadı')
        );

        if ($response->getData()->status === false) {
            return $response;
        }
        return response()->json(['status' => true, 'message' => __('Kullanıcı ve yapılandırmalar başarıyla tamamlandı')]);
    }
    /**
     * PHP-FPM ve Apache yapılandırmalarını oluşturma metodu
     */
    public function addPhpFpm(Request $request)
    {
        $username = $request->input('folder'); // Use 'folder' as it's the username equivalent in this context
        $phpFpmConfigFile = "/etc/php/8.3/fpm/pool.d/$username.conf";


        $phpFpmTemplate = file_get_contents(base_path('server/php/php-fpm.conf'));
        $phpFpmContent = str_replace('TRPANEL_USER', $username, $phpFpmTemplate);

        File::put($phpFpmConfigFile, $phpFpmContent);
        $response = $this->executeCommand(
            'sudo systemctl reload php8.3-fpm',
            __('PHP-FPM başarıyla yeniden yüklendi'),
            __('PHP-FPM yeniden yüklenemedi')
        );

        return $response;
    }
    public function addApache(Request $request)
    {
        $username = $request->input('folder');
        $apacheConfigFile = "/etc/apache2/sites-available/$username.conf";
        $apacheTemplate = file_get_contents(base_path('server/apache/apache.conf'));
        $apacheContent = str_replace('TRPANEL_USER', $username, $apacheTemplate);
        File::put($apacheConfigFile, $apacheContent);

        $response = $this->executeCommand(
            "sudo a2ensite $username.conf",
            __('Apache yapılandırması başarıyla oluşturuldu'),
            __('Apache yapılandırması etkinleştirilemedi')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        $response = $this->executeCommand(
            'sudo apachectl graceful',
            __('Apache yeniden yüklendi'),
            __('Apache yeniden yükleme başarısız')
        );

        return $response;
    }


    public function addPermissions(Request $request)
    {
        $username = $request->input('folder');

        // Step 1: Set the ownership of the home directory to the user
        $response = $this->executeCommand(
            "sudo chown -R $username:www-data /home/$username && sudo chmod -R 755 /home/$username",
            __('Permissions successfully set for home directory'),
            __('Failed to set permissions for home directory')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        // Define directories to be created
        $directories = [
            'public_html',
            'php',
            'php/extensions',
            'logs',
        ];

        foreach ($directories as $directory) {
            // Create the directory using sudo
            $response = $this->executeCommand(
                "sudo mkdir -p /home/$username/$directory",
                __("$directory successfully created"),
                __("Failed to create $directory")
            );

            if ($response->getData()->status === false) {
                return $response;
            }

            // Set the ownership of the created directory to the web server user (www-data)
            $response = $this->executeCommand(
                "sudo chown -R www-data:www-data /home/$username/$directory",
                __("Ownership set for $directory"),
                __("Failed to set ownership for $directory")
            );

            if ($response->getData()->status === false) {
                return $response;
            }

            // Set permissions to allow the web server to access the directory
            $response = $this->executeCommand(
                "sudo chmod 755 /home/$username/$directory",
                __("Permissions set for $directory"),
                __("Failed to set permissions for $directory")
            );

            if ($response->getData()->status === false) {
                return $response;
            }
        }

        $response = $this->executeCommand(
            "sudo chown -R $username:www-data /home/$username && sudo chmod -R 775 /home/$username",
            __('Permissions successfully set for user directories'),
            __('Failed to set permissions for user directories')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        $response = $this->executeCommand(
            "sudo chown $username:www-data /run/php/php8.3-fpm-$username.sock && sudo chmod 660 /run/php/php8.3-fpm-$username.sock",
            __('Permissions successfully set for PHP-FPM socket'),
            __('Failed to set permissions for PHP-FPM socket')
        );

        if ($response->getData()->status === false) {
            return $response;
        }




        return response()->json(['status' => true, 'message' => __('Permissions successfully set for user directories')]);
    }


    public function createIndexPhp(Request $request)
    {
        $username = $request->input('folder');
        $indexPhpFile = "/home/$username/public_html/index.php";
        $indexPhpContent = "<?php \n echo 'hello $username'; \n phpinfo(); \n ?>";

        // Step 1: Ensure the public_html directory exists
        $response = $this->executeCommand(
            "sudo mkdir -p /home/$username/public_html",
            __('public_html directory successfully created'),
            __('Failed to create public_html directory')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        // Step 2: Set permissions and ownership for public_html directory
        $response = $this->executeCommand(
            "sudo chown -R www-data:www-data /home/$username/public_html && sudo chmod -R 755 /home/$username/public_html",
            __('public_html directory permissions successfully set'),
            __('Failed to set public_html directory permissions')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        // Step 3: Create the index.php file
        $response = $this->executeCommand(
            "sudo -u www-data touch $indexPhpFile",
            __('index.php file successfully created'),
            __('Failed to create index.php file')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        // Step 4: Write content to the index.php file
        $response = $this->executeCommand(
            "echo \"$indexPhpContent\" | sudo -u www-data tee $indexPhpFile > /dev/null",
            __('index.php content successfully written'),
            __('Failed to write content to index.php file')
        );

        return $response;
    }


    public function createPhpIni(Request $request)
    {
        $username = $request->input('folder');
        $phpIniFile = "/home/$username/public_html/.user.ini";
        $phpIniTemplate = file_get_contents(base_path('server/php/php.ini'));
        $phpIniContent = str_replace('TRPANEL_USER', $username, $phpIniTemplate);

        $response = $this->executeCommand(
            "sudo -u www-data touch $phpIniFile",
            __('php.ini file successfully created'),
            __('Failed to create php.ini file')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        $response = $this->executeCommand(
            "echo \"$phpIniContent\" | sudo -u www-data tee $phpIniFile > /dev/null",
            __('php.ini content successfully written'),
            __('Failed to write content to php.ini file')
        );

        return $response;
    }



    public function reloadServices(Request $request)
    {
        $response = $this->executeCommand(
            'sudo apachectl graceful',
            __('Apache başarıyla yeniden yüklendi'),
            __('Apache yeniden yüklenemedi')
        );

        if ($response->getData()->status === false) {
            return $response;
        }

        $response = $this->executeCommand(
            'sudo systemctl reload php8.3-fpm',
            __('PHP-FPM başarıyla yeniden yüklendi'),
            __('PHP-FPM yeniden yüklenemedi')
        );

        return $response;
    }

    public function loginUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
            'folder' => 'required|string|max:255',
        ]);
        // kullanıcıya geçiş yap
        $response = $this->executeCommand(
            "sudo su - {$validated['folder']}",
            __('Kullanıcıya geçiş başarılı'),
            __('Kullanıcıya geçiş yapılamadı')
        );
        if ($response->getData()->status === false) {
            return $response;
        }
        $user = User::create($validated);
        event(new Registered($user));
        Auth::login($user);
        return response()->json(['status' => true, 'message' => __('Kullanıcı başarıyla oluşturuldu')]);
    }
}
