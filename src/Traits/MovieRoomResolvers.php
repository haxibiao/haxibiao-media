<?php

namespace Haxibiao\Media\Traits;

use App\MovieRoom;
use App\User;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Media\Image;

trait MovieRoomResolvers
{
    /**
     * 放映室成员
     */
    public function resolveMovieRoomUsers($root, $args, $content, $info)
    {
        return User::whereIn('id', $root->uids);
    }

    //成员进入放映室
    public function resolveUsersInToMovieRoom($root, $args, $content, $info)
    {
        $uids      = $args['uids'];
        $id        = $args['id'];
        $movieRoom = MovieRoom::find($id);
        throw_if(empty($movieRoom), UserException::class, "该放映室不存在！");
        $uids            = array_unique(array_merge($movieRoom->uids, $uids));
        $movieRoom->uids = $uids;
        $movieRoom->save();
        return $movieRoom;
    }

    //创建放映室
    public function resolveSaveMovieRoom($root, $args, $content, $info)
    {
        $name         = $args['name'] ?? "一起看放映室";
        $icon         = $args['icon'] ?? null;
        $movie_id     = $args['movie_id'] ?? null;
        $progress     = $args['progress'] ?? null;
        $series_index = $args['series_index'] ?? null;

        $user = getUser();

        //存在则更新
        $movieRoom = MovieRoom::firstOrNew([
            'user_id' => $user->id,
        ]);
        $movieRoom->forceFill([
            'name'         => $name,
            'movie_id'     => $movie_id,
            'series_index' => $series_index,
            'progress'     => $progress,
        ]);
        $image = Image::saveImage($icon);
        if ($image) {
            $movieRoom->icon = $image->path;
        }
        $movieRoom->save();
        return $movieRoom;
    }
}
