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
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GettingAttacked implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $user;
    public $attackData;

    public function __construct(User $user, $attackData)
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
            'attackData' => [
                'id' => $this->attackData['queue_id'] ?? null,
                'user_id' => $this->attackData['user_id'],
                'action_type' => $this->attackData['action_type'],
                'target_id' => $this->attackData['target_id'],
                'start_time' => Carbon::parse($this->attackData['start_time'])->toIso8601String(),
                'end_time' => Carbon::parse($this->attackData['end_time'])->toIso8601String(),
                'status' => 'in_progress',
                'details' => [
                    'attacker_id' => $this->attackData['user_id'],
                    'defender_id' => $this->attackData['target_id'],
                    'attacker_name' => $this->attackData['attacker_name'] ?? null,
                    'defender_name' => $this->user->name,
                ]
            ]
        ];
    }

}
