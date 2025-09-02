<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orion\Modules\Actionqueue\Services\ActionQueueService;
use Orion\Modules\User\Services\UserResourceService;
use Orion\Modules\User\Services\UserAttributeService;


class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly ActionQueueService $queueService,
        private readonly UserAttributeService $userAttributeService,
        private readonly UserResourceService $userResourceService
    ) {
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
                ? $this->userResourceService->getAllUserResourcesByUserId(Auth::user()->id)
                : [],
            'userAttributes' => Auth::check()
                ? $this->userAttributeService->getAllUserAttributesByUserId(Auth::user()->id)
                : [],
        ]);
    }
}
