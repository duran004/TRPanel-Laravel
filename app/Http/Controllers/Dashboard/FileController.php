<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public string $basePath;
    public array $ignoreFiles = ['.', '..'];

    public function __construct()
    {
        $this->basePath = base_path();
    }

    public function index()
    {
        $path = $_GET['path'] ?? '';
        if ($path == '') {
            $path = $this->basePath . "\\";
        }
        $files = $this->getFiles($path);
        $path = $this->normalizePath($path);
        $this->basePath = $this->normalizePath($this->basePath);

        if (!is_dir($path)) {
            http_response_code(404);
            die('Not a directory');
        }


        // dd($path);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }
        $isOneUpLevel = false;
        // eğer path ve basepath aynı değilse
        if ($path != $this->basePath . "/") {
            $isOneUpLevel = true;
        }
        // eğer path basepath'in içinde değilse
        if (strpos($this->basePath, $path) === 0) {
            $isOneUpLevel = false;
        }

        $data = [
            'title' => 'File Manager',
            'files' => $files,
            'path' => $path,
            'basePath' => $this->basePath,
            'directories' => $this->getDirectories($path),
            'isOneUpLevel' => $isOneUpLevel
        ];
        return view('dashboard.file.index', $data);
    }
    public function normalizePath(string $path)
    {
        return str_replace('\\', '/', $path);
    }
    public function getFiles($path)
    {
        $files = scandir($path);
        $base_path = $path;
        $data = new \stdClass();
        foreach ($files as $file) {
            if (!in_array($file, $this->ignoreFiles)) {
                try {

                    $path = $base_path .  '\\' . $file;
                    // dd($path);
                    $data->$file = new \stdClass();
                    $data->$file->name = $file;
                    $data->$file->path = $path;
                    $data->$file->type = is_dir($path) ? 'dir' : 'file';
                    $data->$file->size = $this->formatSizeUnits(filesize($path));
                    $data->$file->last_modified = date('Y-m-d H:i:s', filemtime($path));
                    $data->$file->permissions = substr(sprintf('%o', fileperms($path)), -4);
                } catch (\Exception $e) {
                    dd($e, $file);
                }
            }
        }
        // dd($data);
        return $data;
    }

    public function checkPath($currentPath)
    {
        // Laravel uygulamasının temel yolunu al
        $basePath = base_path();

        // Gerçek yolları al
        $realCurrentPath = realpath($currentPath);
        $realBasePath = realpath($basePath);

        // Eğer yollar geçerli değilse hata döndür
        if ($realCurrentPath === false || $realBasePath === false) {
            throw new \Exception("Geçersiz yol.");
        }

        // Geçerli yolun temel yolun üstüne çıkıp çıkmadığını kontrol et
        if (strpos($realCurrentPath, $realBasePath) !== 0) {
            //throw new \App\Exceptions\ClientException("Geçersiz yol.");
            die(redirect()->route('filemanager.index', ['path' => '']));
        }

        return true;
    }
    public function getDirectories($path)
    {
        $this->checkPath($path);
        $files = scandir($path);
        $base_path = $path;
        $data = new \stdClass();
        foreach ($files as $file) {
            if (!in_array($file, $this->ignoreFiles)) {
                $path = $base_path . '\\' . $file;
                if (is_dir($path)) {
                    $data->$file = new \stdClass();
                    $data->$file->name = $file;
                    $data->$file->path = $path;
                    $data->$file->type = 'dir';
                }
            }
        }
        return $data;
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }

    public function create()
    {
        $path = $_GET['path'] ?? '';
        if ($path == '') {
            $path = $this->basePath . "\\";
        }
        $path = $this->normalizePath($path);
        $this->basePath = $this->normalizePath($this->basePath);
        $data = [
            'title' => 'Create File',
            'path' => $path,
            'basePath' => $this->basePath,
        ];
        return response()->json(
            [
                'status' => true,
                'message' => view('dashboard.file.create', $data)->render()
            ]
        );
    }

    public function store(Request $request)
    {
        $path = $request->path;
        $name = $request->name;
        $content = $request->content;
        $path = $this->normalizePath($path);
        $this->basePath = $this->normalizePath($this->basePath);
        $path = $path  . '/' . $name;

        file_put_contents($path, $content);

        return response()->json(
            [
                'status' => true,
                'message' => 'File created successfully'
            ]
        );
    }

    public function upload(Request $request)
    {
        $path = $request->path;
        $path = $this->normalizePath($path);
        $this->basePath = $this->normalizePath($this->basePath);

        return response()->json(
            [
                'status' => true,
                'message' => view('dashboard.file.upload', ['path' => $path])->render()
            ]
        );
    }

    public function store_upload(Request $request)
    {
        $path = $request->path;
        $path = $this->normalizePath($path);
        $this->basePath = $this->normalizePath($this->basePath);

        $file = $request->file('file');
        $file->move($path, $file->getClientOriginalName());

        return response()->json(
            [
                'status' => true,
                'message' => 'File uploaded successfully'
            ]
        );
    }

    public function download(Request $request)
    {
        //tek seçimde , ile ayrılmışsa veya klasörse çoklu dosya indirmesi yap
        if (str_contains($request->_files, ',') || is_dir($request->_files)) {
            $this->downloadMultipleFiles($request);
        } else {
            $this->downloadSingleFile($request);
        }
    }

    public function downloadSingleFile($request)
    {
        $file = $request->_files;
        if (file_exists($file)) {

            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($file) . "\"");

            readfile($file);

            return response()->json(
                [
                    'status' => true,
                    'message' => 'File downloaded successfully'
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'File not found'
                ]
            );
        }
    }

    public function downloadMultipleFiles($request)
    {
        try {
            $files = explode(',', $request->_files);
            $zip = new \ZipArchive();
            $zipFileName = 'files' . time() . '.zip';
            if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        if (is_dir($file)) {
                            $this->addFolderToZip($file, $zip);
                        } else {
                            $zip->addFile($file, basename($file));
                        }
                    }
                }
                $zip->close();
            } else {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Zip File not created'
                    ]
                );
            }
            if (file_exists($zipFileName)) {
                header('Content-Type: application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . $zipFileName . "\"");
                readfile($zipFileName);
                unlink($zipFileName);
                return response()->json(
                    [
                        'status' => true,
                        'message' => 'Files downloaded successfully'
                    ]
                );
            } else {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Zip File not found'
                    ]
                );
            }
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    private function addFolderToZip($folder, &$zip, $parentFolder = '')
    {
        $folderName = basename($folder);
        $zip->addEmptyDir($parentFolder . $folderName);
        $files = scandir($folder);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $filePath = $folder . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->addFolderToZip($filePath, $zip, $parentFolder . $folderName . DIRECTORY_SEPARATOR);
            } else {
                $zip->addFile($filePath, $parentFolder . $folderName . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    public function destroy(Request $request)
    {
        //, varsa yada klasörse çoklu silme yap
        if (str_contains($request->_files, ',') || is_dir($request->_files)) {
            $this->deleteMultipleFiles($request);
        } else {
            $this->deleteSingleFile($request);
        }
    }

    public function deleteSingleFile($request)
    {
        $file = $request->_files;
        if (file_exists($file)) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
            } else {
                unlink($file);
            }
            return response()->json(
                [
                    'status' => true,
                    'message' => 'File deleted successfully'
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'File not found'
                ]
            );
        }
    }

    public function deleteMultipleFiles($request)
    {
        $files = explode(',', $request->_files);
        foreach ($files as $file) {
            if (file_exists($file)) {
                if (is_dir($file)) {
                    $this->deleteDirectory($file);
                } else {
                    unlink($file);
                }
            }
        }
        return response()->json(
            [
                'status' => true,
                'message' => 'Files deleted successfully'
            ]
        );
    }

    public function deleteDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {
                        $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
