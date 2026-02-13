<?php

namespace App\Services;

use stdClass;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseService
{
    protected function successMsg(string $message,  array|Collection|Model|JsonResource|int $data=[]):stdClass
    {
        return (object)[
            'status' => true,
            'message' => $message,
            'data' => $data,
            'code' => 200,
        ];
    }

    protected function errorMsg(string $message, int $code):stdClass
    {
        return (object)[
            'status' => false,
            'message' => $message,
            'code' => $code,
        ];
    }
}
