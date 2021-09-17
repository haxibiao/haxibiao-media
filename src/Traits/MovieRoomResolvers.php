<?php

namespace Haxibiao\Media\Traits;

use App\MovieRoom;
use App\User;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Media\Image;

trait MovieRoomResolvers
{

    public function resolveMovieRoom($root, $args, $content, $info)
    {
        $id      = $args['id'] ?? null;
        $user_id = $args['user_id'] ?? null;

        if ($id || $user_id) {
            return MovieRoom::query()->when($id, function ($qb) use ($id) {
                return $qb->where('id', $id);
            })->when($user_id, function ($qb) use ($user_id) {
                return $qb->where('user_id', $user_id);
            })->first();
        }
        return null;
    }
    /**
     * 放映室成员
     */
    public function resolveMovieRoomUsers($root, $args, $content, $info)
    {
        $uids = $root->uids;
        if (empty($uids) || !count($uids)) {
            $uids = [-1];
        }
        return User::whereIn('id', $uids);
    }

    //成员进入放映室
    public function resolveUsersInToMovieRoom($root, $args, $content, $info)
    {
        $uids      = $args['uids'];
        $id        = $args['id'];
        $movieRoom = MovieRoom::find($id);
        throw_if(empty($movieRoom), UserException::class, "该放映室不存在！");
        $uids            = array_unique(array_merge($movieRoom->uids ?? [], $uids));
        $movieRoom->uids = $uids;
        $movieRoom->save();
        return $movieRoom;
    }

    //创建放映室
    public function resolveSaveMovieRoom($root, $args, $content, $info)
    {
        $input        = $args['input'];
        $name         = $input['name'] ?? "一起看放映室";
        $icon         = $input['icon'] ?? null;
        $movie_id     = $input['movie_id'] ?? null;
        $progress     = $input['progress'] ?? null;
        $series_index = $input['series_index'] ?? null;

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
        if ($icon) {
            $image = Image::saveImage($icon);
            if ($image) {
                $movieRoom->icon = $image->path;
            }
        }
        $movieRoom->save();
        return $movieRoom;
    }
}
