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
        return ['status' => true, 'message' => $successMessage];
    }

    /**
     * Kullanıcı oluşturma ve gerekli yapılandırmaların tümünü yapma metodu
     */
    public function registerUser(Request $request)
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

        if (!$response['status']) {
            return $response;
        }

        // Şifre ayarlama
        $response = $this->executeCommand(
            "echo '$username:$password' | sudo chpasswd",
            __('Şifre başarıyla ayarlandı'),
            __('Şifre ayarlanamadı')
        );

        if (!$response['status']) {
            return $response;
        }

        // Ev dizini ayarlama
        $response = $this->executeCommand(
            "sudo usermod -d /home/$username -m $username",
            __('Ev dizini başarıyla ayarlandı'),
            __('Ev dizini ayarlanamadı')
        );

        if (!$response['status']) {
            return $response;
        }

        // PHP-FPM ve Apache yapılandırmalarını oluşturma
        $response = $this->addPhpFpmAndApacheSite($request);

        if (!$response['status']) {
            return $response;
        }

        // Kullanıcıyı veritabanına kaydetme işlemi
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return response()->json(['status' => true, 'message' => __('Kullanıcı ve yapılandırmalar başarıyla tamamlandı')]);
    }

    /**
     * PHP-FPM ve Apache yapılandırmalarını oluşturma metodu
     */
    public function addPhpFpmAndApacheSite(Request $request)
    {
        $username = $request->input('username');
        $phpFpmConfigFile = "/etc/php/8.3/fpm/pool.d/$username.conf";
        $apacheConfigFile = "/etc/apache2/sites-available/$username.conf";

        $phpFpmTemplate = file_get_contents(base_path('server/php/php-fpm.conf'));
        $phpFpmContent = str_replace('TRPANEL_USER', $username, $phpFpmTemplate);

        File::put($phpFpmConfigFile, $phpFpmContent);

        $response = $this->executeCommand(
            'sudo systemctl reload php8.3-fpm',
            __('PHP-FPM başarıyla yeniden yüklendi'),
            __('PHP-FPM yeniden yüklenemedi')
        );

        if (!$response['status']) {
            return $response;
        }

        $apacheTemplate = file_get_contents(base_path('server/apache/apache.conf'));
        $apacheContent = str_replace('TRPANEL_USER', $username, $apacheTemplate);
        File::put($apacheConfigFile, $apacheContent);

        $response = $this->executeCommand(
            "sudo a2ensite $username.conf",
            __('Apache yapılandırması başarıyla oluşturuldu'),
            __('Apache yapılandırması etkinleştirilemedi')
        );

        if (!$response['status']) {
            return $response;
        }

        $response = $this->executeCommand(
            'sudo apachectl graceful',
            __('Apache yeniden yüklendi'),
            __('Apache yeniden yükleme başarısız')
        );

        return $response;
    }
}