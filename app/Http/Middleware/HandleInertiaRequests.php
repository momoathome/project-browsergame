<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Services\QueueService;

class HandleInertiaRequests extends Middleware
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'userResources' => Auth::check()
                ? UserResource::where('user_id', Auth::user()->id)
                    ->with('resources')
                    ->orderBy('resource_id', 'asc')
                    ->get()
                : [],
            'userAttributes' => Auth::check()
                ? UserAttribute::where('user_id', Auth::user()->id)
                ->orderBy('id', 'asc')
                ->get()
                : [],
            'queue' => Auth::check()
                ? $this->queueService->getPlayerQueue(Auth::user()->id)
                : [],
        ]);
    }
}
