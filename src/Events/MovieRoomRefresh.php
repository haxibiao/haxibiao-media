<?php

namespace Haxibiao\Breeze\Events;

use App\MovieRoom;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MovieRoomRefresh implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $movieRoom;

    public function __construct(MovieRoom $movieRoom)
    {
        $this->movieRoom = $movieRoom;
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
        $data = [
            'icon'         => $this->movieRoom->icon,
            'name'         => $this->movieRoom->name,
            'movie_id'     => $this->movieRoom->movie_id,
            'progress'     => $this->movieRoom->progress,
            'series_index' => $this->movieRoom->series_index,
        ];
        return $data;
    }
}
