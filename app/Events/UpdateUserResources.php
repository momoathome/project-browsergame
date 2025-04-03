<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Orion\Modules\Asteroid\Models\Asteroid;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UpdateUserResources implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.update.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'resources.updated';
    }

    public function broadcastWith()
    {
        return [
            'resources' => $this->user->resources->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'amount' => $resource->pivot->amount
                ];
            }),
        ];
    }
}
