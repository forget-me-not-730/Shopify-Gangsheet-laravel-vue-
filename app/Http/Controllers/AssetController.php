<?php

namespace App\Http\Controllers;

use App\Models\CustomerImage;

class AssetController extends Controller
{
    public function getThumbImage($image_name)
    {
        $name = explode('.', $image_name)[0];

        $path = "images/thumbs/$name.png";
        if (!spaces()->exists($path)) {
            $success = CustomerImage::makeThumbImage($name);

            if ($success) {
                return redirect()->to(spaces()->url($path));
            }
        }

        return redirect()->to(spaces()->url("images/$name.png"));
    }
}
