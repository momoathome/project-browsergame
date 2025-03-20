<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Models\UserResource;
use App\Models\UserAttribute;
use App\Services\QueueService;
use App\Services\UserAttributeService;


class HandleInertiaRequests extends Middleware
{
    protected $queueService;
    protected $userAttributeService;

    public function __construct(QueueService $queueService, UserAttributeService $userAttributeService)
    {
        $this->queueService = $queueService;
        $this->userAttributeService = $userAttributeService;
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
                    ->with('resource')
                    ->orderBy('resource_id', 'asc')
                    ->get()
                : [],
            'userAttributes' => Auth::check()
                ? $this->userAttributeService->getUserAttributes(Auth::user()->id)
                : [],
            'queue' => Auth::check()
                ? $this->queueService->getPlayerQueue(Auth::user()->id)
                : [],
        ]);
    }
}
