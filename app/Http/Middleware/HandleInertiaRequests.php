<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orion\Modules\User\Models\UserResource;
use Orion\Modules\Actionqueue\Services\QueueService;
use Orion\Modules\User\Services\UserAttributeService;


class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly UserAttributeService $userAttributeService
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
        if (Auth::check()) {
            $this->queueService->processQueueForUser(Auth::user()->id);
        }

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
