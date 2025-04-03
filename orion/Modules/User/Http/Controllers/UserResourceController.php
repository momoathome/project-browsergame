<?php

namespace Orion\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Orion\Modules\User\Services\UserResourceService;

class UserResourceController extends Controller
{
    public function __construct(
        private readonly UserResourceService $userResourceService
    ) {
    }

    public function index()
    {
        //
    }

    /* For Testing Purposes */
    public function addResource(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'amount' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $resourceId = $request->input('resource_id');
        $amount = $request->input('amount');

        $userResource = $this->userResourceService->getSpecificUserResource($user->id, $resourceId);

        if ($userResource) {
            $this->userResourceService->addResourceAmount($user, $resourceId, $amount);
        } else {
            $this->userResourceService->createUserResource($user->id, $resourceId, $amount);
        }
    }

    public function updateResourceAmount(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:0',
            'user_id' => 'required|exists:users,id',
        ]);

        $userResource = $this->userResourceService->getSpecificUserResource($request->user_id, $id);

        if ($userResource) {
            $this->userResourceService->updateResourceAmount($request->user_id, $id, $request->amount);
        } else {
            return redirect()->back()->with('error', 'User resource not found');
        }
        
        return redirect()->back()->with('message', 'Resource updated successfully');
    }
}
