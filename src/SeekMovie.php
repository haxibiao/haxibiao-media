<?php

namespace Haxibiao\Media;

use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Breeze\Model;
use Haxibiao\Breeze\Traits\HasFactory;
use Haxibiao\Breeze\Traits\ModelHelpers;
use Haxibiao\Breeze\User;
use Haxibiao\Media\Traits\CanLinkMovie;
use Haxibiao\Media\Traits\WithImage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SeekMovie extends Model
{
    use HasFactory;
    use WithImage;
    use ModelHelpers;
    use CanLinkMovie;
    protected $fillable = [
        'name',
        'user_id',
        'description',
        'movie_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveCreateSeekMovie($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user        = getUser(false);
        $name        = data_get($args, "name");
        $description = data_get($args, "description");

        $seekMovie = SeekMovie::firstOrCreate([
            'user_id' => $user->id,
            'name'    => $name,
        ], [
            'description' => $description,
        ]);

        if (!empty($args['images'])) {
            $imageIds = [];
            foreach ($args['images'] as $image) {
                $image      = Image::saveImage($image);
                $imageIds[] = $image->id;
            }
            $seekMovie->images()->sync($imageIds);
        }

        return $seekMovie;
    }
}
