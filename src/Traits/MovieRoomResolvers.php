<?php

namespace Haxibiao\Media\Traits;

use App\MovieRoom;
use App\User;
use Haxibiao\Breeze\Exceptions\UserException;
use Haxibiao\Media\Events\MovieRoomRefresh;
use Haxibiao\Media\Events\MovieRoomUserEvent;
use Haxibiao\Media\Image;
use Haxibiao\Sns\Chat;

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
        foreach ($uids as $uid) {
            //用户进入放映室
            if (!in_array($uid, $movieRoom->uids)) {
                event(new MovieRoomUserEvent($movieRoom, $uid, true));
            }
        }
        $uids            = array_unique(array_merge($movieRoom->uids ?? [], $uids));
        $movieRoom->uids = $uids;
        $movieRoom->save();
        //放映室成员同步到放映室聊天群
        $chat = $movieRoom->chat;
        if ($chat) {
            $chat->uids = $uids;
            $chat->save();
            if (count($uids) > Chat::MAX_USERS_NUM) {
                throw new \Exception('人数超过上限！');
            }
        }
        return $movieRoom;
    }

    public function resolveUsersLeaveToMovieRoom($root, $args, $content, $info)
    {
        $uids      = $args['uids'];
        $id        = $args['id'];
        $movieRoom = MovieRoom::find($id);
        throw_if(empty($movieRoom), UserException::class, "该放映室不存在！");
        foreach ($uids as $uid) {
            //用户离开放映室
            if (in_array($uid, $movieRoom->uids)) {
                event(new MovieRoomUserEvent($movieRoom, $uid, false));
            }
        }
        $uids            = array_unique(array_diff($movieRoom->uids ?? [], $uids));
        $movieRoom->uids = $uids;
        $movieRoom->save();
        //放映室成员同步到放映室聊天群
        $chat = $movieRoom->chat;
        if ($chat) {
            $chat->uids = $uids;
            $chat->save();
            if (count($uids) > Chat::MAX_USERS_NUM) {
                throw new \Exception('人数超过上限！');
            }
        }
        return $movieRoom;
    }

    //创建放映室
    public function resolveSaveMovieRoom($root, $args, $content, $info)
    {
        $input        = $args['input'];
        $name         = $input['name'] ?? null;
        $icon         = $input['icon'] ?? null;
        $movie_id     = $input['movie_id'] ?? null;
        $progress     = $input['progress'] ?? null;
        $series_index = $input['series_index'] ?? null;

        $user = getUser();

        //存在则更新
        $movieRoom = MovieRoom::firstOrNew([
            'user_id'  => $user->id,
            'movie_id' => $movie_id,
        ]);

        //切换或者创建播放资源
        if ($name) {
            $movieRoom->name = $name;
        }
        if ($icon) {
            $movieRoom->icon = $icon;
        }
        if ($movie_id) {
            $movieRoom->movie_id = $movie_id;
        }
        if ($progress) {
            $movieRoom->progress = $progress;
        }
        if ($series_index) {
            $movieRoom->series_index = $series_index;
        }

        //第一次创建放映室，一起创建放映室聊天群
        if (empty($movieRoom->id)) {
            $chat = Chat::create([
                'subject' => $name ?? "一起看放映室",
                'status'  => Chat::PUBLIC_STATUS, //默认公开群聊
                'uids'    => [$user->id], //初始成员
                'user_id' => $user->id, // 聊天发起人（群主）
                'type'    => Chat::GROUP_TYPE, //聊天类型为群聊
            ]);
            $movieRoom->chat_id = $chat->id;
            $movieRoom->name    = "一起看放映室";
            $movieRoom->uids    = [$user->id];
        }

        //可能会更新logo
        if ($icon) {
            $image = Image::saveImage($icon);
            if ($image) {
                $movieRoom->icon = $image->path;
            }
        }
        $movieRoom->save();
        event(new MovieRoomRefresh($movieRoom));
        return $movieRoom;
    }
}