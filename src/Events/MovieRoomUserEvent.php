<?php

namespace Haxibiao\Media\Events;

use App\MovieRoom;
use App\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovieRoomUserEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $movieRoom;
    public $status;
    public $user_id;

    public function __construct(MovieRoom $movieRoom, $user_id, $status = true)
    {
        $this->movieRoom = $movieRoom;
        $this->status    = $status;
        $this->user_id   = $user_id;
        //不广播给当前用户
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn()
    {
        if (in_array(config('app.name'), ['haxibiao', 'yinxiangshipin', 'juhaokan'])) {
            return new PresenceChannel(config('app.name') . '.movie.room.' . $this->movieRoom->id);
        }
        return new PresenceChannel('movie.room.' . $this->movieRoom->id);
    }

    public function broadcastWith()
    {
        $user = User::find($this->user_id);
        if ($user) {
            if ($this->status) {
                $message = $user->name . '进入了房间';
            } else {
                $message = $user->name . '退出了房间';
            }
            $data = [
                'message' => $message,
            ];
            return $data;
        }
    }
}