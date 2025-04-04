<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Orion\Modules\Actionqueue\Dto\ActionQueueDTO;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GettingAttacked implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $user;
    public ActionQueueDTO $attackData;

    public function __construct(User $user, ActionQueueDTO $attackData)
    {
        $this->user = $user;
        $this->attackData = $attackData;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.combat.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'user.attacked';
    }

    public function broadcastWith()
    {
        return [
            'attackData' => $this->attackData,
        ];
    }

}
