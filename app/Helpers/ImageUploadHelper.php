<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadHelper
{
    public static function upload(UploadedFile $file, $folder = 'images')
    {
        $file_name = rand() . time() . '.' . $file->getClientOriginalExtension();
        $file->move($folder, $file_name);
        return '/' . $folder . '/' . $file_name;
    }
}