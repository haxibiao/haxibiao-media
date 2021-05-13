<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;

trait ImageResolvers
{
    //resolvers
    public static function resolveUploadImage($root, $args, $context = null, $info = null)
    {
        if (is_testing_env()) {
            return [];
        }

        if (currentUser()) {
            $images    = $args['image']; // [base64String]
            $imageUrls = [];
            foreach ($images as $image) {
                if ($imageObj = Image::saveImage($image)) {
                    $imageUrls[] = $imageObj->url;
                } else {
                    $imageUrls[] = null;
                }
            }
            return $imageUrls;
        }
    }
}
