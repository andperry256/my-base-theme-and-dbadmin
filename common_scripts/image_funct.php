<?php
//================================================================================
if (!defined('IMAGE_FUNCT_DEFINED')):
//================================================================================
/*
Function resize_image_to_webp

This function converts an image file to webp with the option to resize the image.

This function makes use of an external array $image_dimensions, which must be
defined by the calling software. Each element is of the format:

    $key => $value

where $key is an identifying mnemonic and $value is the size of the long dimension
of the destination image. If the new dimesion would require an increase in image
size, then the image will be saved with no change in size.
*/
//================================================================================

function resize_image_to_webp($source_path, $destination_path, $type)
{
    global $image_dimensions;

    if (!is_file($source_path)) {
        return false;
    }

    // Get image dimensions and create new image resource from source file
    list($original_width, $original_height) = getimagesize($source_path);
    $fileext = strtolower(pathinfo($source_path,PATHINFO_EXTENSION));
    switch ($fileext) {
        case 'jpg':
            $source_image  = imagecreatefromjpeg ($source_path);
            break;
        case 'png':
            $source_image  = imagecreatefrompng ($source_path);
            break;
        case 'gif':
            $source_image  = imagecreatefromgif ($source_path);
            break;
        case 'webp':
            $source_image  = imagecreatefromwebp ($source_path);
            break;
        default:
            return false;
    }

    // Calculate new dimensions
    if (isset($image_dimensions[$type])) {
        $new_dimension = $image_dimensions[$type];
    }
    else {
        return false;
    }
    if ($original_width > $original_height) {
        $new_width = ($original_width >= $new_dimension) ? $new_dimension : $original_width;
        $new_height = round($new_width * $original_height / $original_width);
    }
    else {
        $new_height = ($original_height >= $new_dimension) ? $new_dimension : $original_height;
        $new_width = round($new_height * $original_width / $original_height);
    }

    // Create and resize new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Save new image as a WebP file and free up memory
    $result = imagewebp($new_image, $destination_path, 80);
    imagedestroy($source_image);
    imagedestroy($new_image);
    return $result;
}

//================================================================================
define ( 'IMAGE_FUNCT_DEFINED', true );
endif;
//================================================================================
