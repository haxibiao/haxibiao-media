<?php

namespace Haxibiao\Media\Events;

use Haxibiao\Media\Danmu;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DanmuEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $danmu;
    public $movie_id;
    public $series_name;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Danmu $danmu, $movie_id, $series_name)
    {
        $this->danmu       = $danmu;
        $this->movie_id    = $movie_id;
        $this->series_name = $series_name;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('danmu_' . $this->movie_id . '_' . $this->series_name);
    }

    public function broadcastWith()
    {
        return [
            'movie_id'    => $this->danmu->movie_id,
            'series_name' => $this->danmu->series_name,
            'content'     => $this->danmu->content,
            'user_id'     => $this->danmu->user_id,
        ];
    }
}
