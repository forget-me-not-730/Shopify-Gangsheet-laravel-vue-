<?php

namespace App\Http\Controllers\Auth;

use App\GangSheet\Facades\Dropbox;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DropboxAuthController extends Controller
{
    public function success(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);

            try {
                $code = $data['code'];
                $state = $data['state'];

                $params = json_decode(base64_decode($state), true);
                $user_id = $params['user_id'];

                $user = User::findOrFail($user_id);

                $result = Dropbox::authorizeStoreFromCode($code, $params);

                $user->setSetting('dropboxConnected', true);

                if ($result['success']) {
                    return '<script>window.close()</script>';
                } else {
                    return $result['response'];
                }

            } catch (\Exception $exception) {
                return $exception->getMessage();
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
