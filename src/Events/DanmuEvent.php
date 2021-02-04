<?php

namespace Haxibiao\Media\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DanmuEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $danmu;
    public $movie_id;
    public $series_index;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($danmu, $movie_id, $series_index)
    {
        $this->danmu        = $danmu;
        $this->movie_id     = $movie_id;
        $this->series_index = $series_index;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('danmu_' . $this->movie_id . '_' . $this->series_index);
    }

    public function broadcastWith()
    {
        return [
            'id'      => $this->danmu->series_id,
            'content' => $this->danmu->content,
            'user_id' => $this->danmu->user_id,
            'color'   => $this->danmu->color,
            'type'    => $this->danmu->type,
        ];
    }
}
