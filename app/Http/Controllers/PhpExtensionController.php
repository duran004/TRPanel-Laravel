<?php

namespace App\Http\Controllers;

use App\Models\PHPExtension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhpExtensionController extends Controller
{
    public function index()
    {
        $user = Auth::user(); // Giriş yapmış kullanıcıyı al
        $extensions = PHPExtension::where('user_id', $user->id)->get();  // Kullanıcının extension'larını al
        return view('extensions.index', compact('extensions', 'user'));
    }

    // Toplu İşlem: Aktif etme, devre dışı bırakma ve silme
    public function bulkAction(Request $request)
    {
        $user = Auth::user(); // Giriş yapmış kullanıcıyı al
        $selectedExtensions = $request->input('selected_extensions', []); // Seçilen eklentiler

        if (empty($selectedExtensions)) {
            return back()->with('error', 'Hiçbir eklenti seçilmedi!');
        }

        $extensions = PHPExtension::whereIn('id', $selectedExtensions)->where('user_id', $user->id)->get();

        switch ($request->input('action')) {
            case 'activate':
                foreach ($extensions as $extension) {
                    $extension->enable(); // Eklentiyi aktif et
                    $this->addPhpExtensionToConfig($user->username, $extension->name); // PHP-FPM'e ekle
                }
                return back()->with('success', 'Seçilen eklentiler aktif edildi!');

            case 'deactivate':
                foreach ($extensions as $extension) {
                    $extension->disable(); // Eklentiyi devre dışı bırak
                    $this->removePhpExtensionFromConfig($user->username, $extension->name); // PHP-FPM'den çıkar
                }
                return back()->with('success', 'Seçilen eklentiler devre dışı bırakıldı!');

            case 'delete':
                foreach ($extensions as $extension) {
                    $this->removePhpExtensionFromConfig($user->username, $extension->name); // PHP-FPM'den çıkar
                    $extension->delete(); // Eklentiyi veritabanından sil
                }
                return back()->with('success', 'Seçilen eklentiler silindi!');

            default:
                return back()->with('error', 'Geçersiz işlem!');
        }
    }

    // PHP-FPM konfigürasyon dosyasına eklenti ekleme
    private function addPhpExtensionToConfig($username, $extension)
    {
        $phpFpmConfigFile = "/etc/php/8.3/fpm/pool.d/{$username}.conf";
        if (file_exists($phpFpmConfigFile)) {
            $phpFpmConfigContent = file_get_contents($phpFpmConfigFile);
            $extensionLine = "php_admin_value[extension] = $extension";
            if (strpos($phpFpmConfigContent, $extensionLine) === false) {
                $phpFpmConfigContent .= "\n" . $extensionLine;
                file_put_contents($phpFpmConfigFile, $phpFpmConfigContent);
                exec('sudo systemctl reload php8.3-fpm');
            }
        }
    }

    // PHP-FPM konfigürasyon dosyasından eklenti çıkarma
    private function removePhpExtensionFromConfig($username, $extension)
    {
        $phpFpmConfigFile = "/etc/php/8.3/fpm/pool.d/{$username}.conf";
        if (file_exists($phpFpmConfigFile)) {
            $phpFpmConfigContent = file_get_contents($phpFpmConfigFile);
            $extensionLine = "php_admin_value[extension] = $extension";
            if (strpos($phpFpmConfigContent, $extensionLine) !== false) {
                $phpFpmConfigContent = str_replace($extensionLine, '', $phpFpmConfigContent);
                file_put_contents($phpFpmConfigFile, $phpFpmConfigContent);
                exec('sudo systemctl reload php8.3-fpm');
            }
        }
    }
}
