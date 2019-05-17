<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request)
    {
        $notification = auth()->user()->notifications()->where("id", $request->id)->firstorfail();
        if($notification->read_at == null)
        {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json($notification);
    }

    public function unreads(Request $request)
    {
        $unreads = auth()->user()->unreadNotifications;

        return response()->json($unreads);
    }
}
