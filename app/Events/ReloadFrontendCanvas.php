<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Orion\Modules\Asteroid\Models\Asteroid;

class ReloadFrontendCanvas implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $asteroid;
    public function __construct(Asteroid $asteroid)
    {
        $this->asteroid = $asteroid;
    }

    public function broadcastOn()
    {
        return new Channel('canvas');
    }

    public function broadcastAs()
    {
        return 'reload.canvas';
    }

    public function broadcastWith()
    {
        return [
            'asteroid' => $this->asteroid->load(['resources']),
        ];
    }
}
