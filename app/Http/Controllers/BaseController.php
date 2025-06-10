<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller as BaseRoutingController;
use Illuminate\Support\Facades\Auth;

abstract class BaseController extends BaseRoutingController
{
    use AuthorizesRequests, ValidatesRequests;

    public function getAuthUser(): User
    {
        return Auth::user();
    }

    public function responseData($data, $status = 200)
    {
        return response($data, $status);
    }
}
