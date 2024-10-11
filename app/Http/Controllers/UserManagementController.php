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
        $response = $this->executeCommand(
            "sudo chown -R $username:$username /home/$username",
            __('İzinler başarıyla ayarlandı'),
            __('İzinler ayarlanamadı')
        );
        if ($response->getData()->status === false) {
            return $response;
        }
        $directories = [
            'public_html',
            'php',
            'php/extensions',
            'logs',
        ];
        foreach ($directories as $directory) {
            $create = File::makeDirectory("/home/$username/$directory", 0755, true);
            if (!$create) {
                return response()->json(['status' => false, 'message' => __("$directory oluşturulamadı")], 500);
            }
            $response = $this->executeCommand(
                "sudo chown -R $username:$username /home/$username/$directory",
                __("$directory başarıyla oluşturuldu"),
                __("{$directory} oluşturulamadı")
            );
            if ($response->getData()->status === false) {
                return $response;
            }
        }
        $phpFpmSocket = "/var/run/php/php8.3-fpm-$username.sock";
        $response = $this->executeCommand(
            "sudo chown $username:www-data $phpFpmSocket",
            __('PHP-FPM soketi chown başarıyla ayarlandı'),
            __('PHP-FPM soketi chown ayarlanamadı')
        );
        if ($response->getData()->status === false) {
            return $response;
        }
        $response = $this->executeCommand(
            "sudo chmod 775 $phpFpmSocket",
            __('PHP-FPM soketi chmod başarıyla ayarlandı'),
            __('PHP-FPM soketi chmod ayarlanamadı')
        );
        if ($response->getData()->status === false) {
            return $response;
        }
        $response = $this->executeCommand(
            "sudo usermod -a -G www-data $username",
            __('Kullanıcıya www-data grubu eklendi'),
            __('Kullanıcıya www-data grubu eklenemedi')
        );
        if ($response->getData()->status === false) {
            return $response;
        }
        $response = $this->executeCommand(
            "sudo a2dissite 000-default.conf",
            __('Varsayılan site devre dışı bırakıldı'),
            __('Varsayılan site devre dışı bırakılamadı')
        );
        if ($response->getData()->status === false) {
            return $response;
        }

        return response()->json(['status' => true, 'message' => __('İzinler başarıyla ayarlandı')]);
    }
}
