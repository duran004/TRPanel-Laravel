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
     * General method to execute commands with error handling
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
     * Register the user in the system and create the necessary home directory
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

        // Step 1: Create the user
        $response = $this->executeCommand(
            "sudo adduser --disabled-password --gecos '' $username",
            __('User successfully created'),
            __('User could not be created')
        );

        if (!$response['status']) {
            return $response;
        }

        // Step 2: Set the user password
        $response = $this->executeCommand(
            "echo '$username:$password' | sudo chpasswd",
            __('Password successfully set'),
            __('Failed to set password')
        );

        if (!$response['status']) {
            return $response;
        }

        // Step 3: Set up the user's home directory
        $response = $this->executeCommand(
            "sudo usermod -d /home/$username -m $username",
            __('Home directory successfully set'),
            __('Failed to set home directory')
        );

        return $response;
    }

    /**
     * Set up PHP-FPM and Apache configurations
     */
    public function addPhpFpmAndApacheSite(Request $request)
    {
        $username = $request->input('folder');
        $phpFpmConfigFile = "/etc/php/8.3/fpm/pool.d/$username.conf";
        $apacheConfigFile = "/etc/apache2/sites-available/$username.conf";

        // Create PHP-FPM config
        $phpFpmTemplate = file_get_contents(base_path('server/php/php-fpm.conf'));
        $phpFpmContent = str_replace('TRPANEL_USER', $username, $phpFpmTemplate);
        File::put($phpFpmConfigFile, $phpFpmContent);

        $response = $this->executeCommand(
            'sudo systemctl reload php8.3-fpm',
            __('PHP-FPM successfully reloaded'),
            __('Failed to reload PHP-FPM')
        );

        if (!$response['status']) {
            return $response;
        }

        // Create Apache config
        $apacheTemplate = file_get_contents(base_path('server/apache/apache.conf'));
        $apacheContent = str_replace('TRPANEL_USER', $username, $apacheTemplate);
        File::put($apacheConfigFile, $apacheContent);

        // Enable Apache site configuration
        $response = $this->executeCommand(
            "sudo a2ensite $username.conf",
            __('Apache configuration successfully created'),
            __('Failed to enable Apache configuration')
        );

        if (!$response['status']) {
            return $response;
        }

        // Gracefully reload Apache to apply changes
        $response = $this->executeCommand(
            'sudo apachectl graceful',
            __('Apache successfully reloaded'),
            __('Failed to reload Apache')
        );

        return $response;
    }

    /**
     * Save user to the database
     */
    public function saveUserToDatabase(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return response()->json(['status' => true, 'message' => __('User successfully registered')]);
    }
}
