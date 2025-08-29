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
    if (($fileext == 'png') || ($fileext == 'webp')) {
        // Preserve transparency
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Save new image as a WebP file and free up memory
    $result = imagewebp($new_image, $destination_path, 80);
    imagedestroy($source_image);
    imagedestroy($new_image);
    return $result;
}

//================================================================================
/*
Function update_wp_web_images

This function is called, normally as part of the WP cron additions for a given
site. It is responsible for maintaining sub-directories within the main WP uploads
directory, each of which contains copies of all the image uploads, scaled to a
specific dimension.

The $image_dimensions array (see above description of resize_image_to_webp) must
be defined by the calling software.
*/
//================================================================================

function update_wp_web_images()
{
    global $base_dir, $image_dimensions;
    if (is_dir("$base_dir/wp-content/uploads") && (isset($image_dimensions))) {
        $exts = ['jpg'=>true, 'png'=>true, 'webp'=>true];

        // Loop through entries in $image_dimensions.
        //foreach ($image_dimensions as $type => $size) print("$type => $size\n");
        foreach ($image_dimensions as $type => $size)  {
            $pos = strpos($type,'__');
            $filename_prefix = ($pos !== false) ? substr($type,$pos+2) : '';
            $prefix_length = strlen($filename_prefix);

            // Create uploads sub-directory if not present.
            if (!is_dir("$base_dir/wp-content/uploads/$type")) {
                mkdir("$base_dir/wp-content/uploads/$type",0775);
            }

            /*
            Loop through files in the source directory. A file is eligible to be converted if:
            1. It is an image with an appropriate file extension, and:
            2. There is either no filename prefix specified, or there is one and it matches the filename.
            */
            $dirlist = scandir("$base_dir/wp-content/uploads");
            foreach ($dirlist as $file) {
                if (is_file("$base_dir/wp-content/uploads/$file")) {
                    $source_path = "$base_dir/wp-content/uploads/$file";
                    $file_root = pathinfo($source_path,PATHINFO_FILENAME);
                    $file_ext = pathinfo($source_path,PATHINFO_EXTENSION);
                    if ((isset($exts[$file_ext])) &&
                        ((empty($prefix_length)) || (substr($file_root,0,$prefix_length+1) == $filename_prefix.'_'))) {

                        // Create destination image if not present or source image is newer.
                        $dest_path = "$base_dir/wp-content/uploads/$type/$file_root.webp";
                        if ((!is_file($dest_path)) || (filemtime($source_path) > filemtime($dest_path))) {
                            resize_image_to_webp($source_path, $dest_path, $type);
                        }
                    }
                }
            }

            // Delete any orphan files in the destination directory.
            $dirlist = scandir("$base_dir/wp-content/uploads/$type");
            foreach ($dirlist as $file) {
                $dest_path = "$base_dir/wp-content/uploads/$type/$file";
                if (is_file($dest_path)) {
                    $source_found = false;
                    $file_root = pathinfo($dest_path,PATHINFO_FILENAME);
                    foreach ($exts as $ext => $dummy) {
                        if (is_file("$base_dir/wp-content/uploads/$file_root.$ext")) {
                            $source_found = true;
                            break;
                        }
                    }
                    if (!$source_found) {
                        print("Deleting $dest_path\n");
                        unlink("$dest_path");
                    }
                }
            }
        }
    }
}

//================================================================================
define ( 'IMAGE_FUNCT_DEFINED', true );
endif;
//================================================================================
