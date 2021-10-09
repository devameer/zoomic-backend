<?php

namespace App\Utils;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadImages
{
    public static function upload(string $path, UploadedFile $image)
    {
        if($image != null){
            $file_name = implode('.', explode('.', $image->getClientOriginalName(), -1));
            $file_name = time().'.'.str_replace([' ', '.'], '_', $file_name) . '.' . $image->extension();
            if($image->move($path, $file_name)){
                return "{$path}/{$file_name}";
            }
        }
        return null;
    }

    public static function delete(string $path, string $filename){
        $file = '/'.public_path().'/'.$path.'/'.$filename;
        if(file_exists($file)){
            unlink($file);
        }
        return null;
    }
}
