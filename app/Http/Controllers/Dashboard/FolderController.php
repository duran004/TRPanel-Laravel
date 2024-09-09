<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    private string $basePath;
    private bool $isLinux;
    public function __construct()
    {
        $this->isLinux = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
        $userFolder = Auth::user()->folder;
        $this->basePath = $this->isLinux ? env('LINUX_HOME')  . $userFolder : env('WINDOWS_HOME') . $userFolder;
        $this->basePath = $this->normalizePath($this->basePath) . "/";
        //current path base pathin yukarısına çıkamaz
    }
    public function checkPath($currentPath)
    {
        // Laravel uygulamasının temel yolunu al
        $basePath = $this->basePath;

        // Gerçek yolları al
        $realCurrentPath = realpath($currentPath);
        $realBasePath = realpath($basePath);


        // Eğer yollar geçerli değilse hata döndür
        if ($realCurrentPath === false || $realBasePath === false) {
            return false;
        }

        // Geçerli yolun temel yolun üstüne çıkıp çıkmadığını kontrol et
        if (strpos($realCurrentPath, $realBasePath) !== 0) {
            return false;
            return (redirect()->route('filemanager.index', ['path' => ''])->send());
        }

        return true;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = [
            'path' => $_GET['path'] ?? ''
        ];

        return response()->json([
            'status' => true,
            'message' => view('dashboard.folder.create', $data)->render()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            $path = $request->path;
            $folder = $request->name;
            if ($path == '') {
                $path = $this->basePath;
            } else {
                $path = $path . "/";
                if (!$this->checkPath($path)) {
                    return response()->json([
                        'status' => false,
                        'message' => __('Invalid path'),
                        'path' => $path
                    ], 400);
                }
            }
            $path = $this->normalizePath($path . $folder);

            if (file_exists($path)) {
                return response()->json([
                    'status' => false,
                    'message' => __('Folder already exists')
                ], 400);
            }

            mkdir($path, 0777, true);
            return response()->json([
                'status' => true,
                'message' => $path . " " . __('Folder created successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function normalizePath(string $path)
    {
        return rtrim(str_replace('\\', '/', $path), "/");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}