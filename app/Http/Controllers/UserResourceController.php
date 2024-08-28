<?php

namespace App\Http\Controllers;

use App\Models\UserResource;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserResourceController extends Controller
{
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

        $userResource = UserResource::where('user_id', $user->id)
            ->where('resource_id', $resourceId)
            ->first();

        if ($userResource) {
            $userResource->count += $amount;
            $userResource->save();
        } else {
            UserResource::create([
                'user_id' => $user->id,
                'resource_id' => $resourceId,
                'count' => $amount,
            ]);
        }
    }
}
