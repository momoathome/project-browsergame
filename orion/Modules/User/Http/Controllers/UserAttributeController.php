<?php

namespace Orion\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Orion\Modules\User\Services\UserAttributeService;

class UserAttributeController extends Controller
{
    public function __construct(
        private readonly UserAttributeService $userAttributeService,
        private readonly AuthManager $authManager
    ) {
    }

    public function index()
    {
        //
    }

    public function getAllAttributes()
    {
        // return all user attributes for the authenticated user
        $user = $this->authManager->user();
        $userAttributes = $this->userAttributeService->getAllUserAttributesByUserId($user->id);

        return response()->json([
            'userAttributes' => $userAttributes,
        ], 200);
    }

}
