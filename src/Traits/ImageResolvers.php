<?php

namespace haxibiao\media\Traits;

use haxibiao\media\Image;

trait ImageResolvers
{
    //resolvers
    public static function resolveUploadImage($root, $args, $context = null, $info = null)
    {
        if (checkUser()) {
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
