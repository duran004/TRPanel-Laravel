<?php

namespace App\Exceptions;

use Exception;

class ClientException extends Exception
{
    public function render($request)
    {
        // return response()->json([
        //     'status' => false,
        //     'message' => view('errors.client', ['message' => $this->getMessage()])->render()
        // ]);
        return \view('errors.client', ['message' => $this->getMessage()]);
    }
}