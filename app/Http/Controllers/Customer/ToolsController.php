<?php

namespace App\Http\Controllers\Customer;

use App\GangSheet\Facades\DripApps;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ToolsController extends Controller
{
    public function autoNest(Request $request)
    {
        $data = $request->validate([
            'variants' => 'required|array',
            'rectangles' => 'required|array',
            'margin' => 'nullable|numeric',
            'artboardMargin' => 'nullable|numeric',
            'hiddenVariants' => 'nullable|array',
            'visibleVariants' => 'nullable|array',
        ]);

        $response = DripApps::autoNest($data);

        return response()->json($response);
    }

    public function removeBackground(Request $request)
    {
        $data = $this->validate($request, [
            'image' => 'required',
        ]);

        $response = DripApps::removeBg($data);

        if ($response['success']) {
            $filename = Str::uuid();
            $originPath = temp_path("$filename.png");
            spaces()->put($originPath, $response['image']);

            return response()->json([
                'success' => true,
                'url' => spaces()->url($originPath)
            ]);
        }

        return response()->json($response);
    }
}
