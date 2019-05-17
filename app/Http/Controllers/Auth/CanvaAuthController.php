<?php

namespace App\Http\Controllers\Auth;

use App\GangSheet\Facades\Canva;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CanvaAuthController extends Controller
{
    public function getAuthorizeUrl(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|string',
            'user_id' => 'required|numeric',
            'customer_id' => 'nullable|numeric',
        ]);

        $token = Canva::getConnectedCustomerToken($data);
        $authorizeUrl = Canva::getAuthorizeUrl($data);

        return response()->json([
            'success' => true,
            'authorize_url' => $authorizeUrl,
            'access_token' => $token->access_token ?? null,
            'email' => $token->email ?? null,
            'name' => $token->name ?? null,
        ]);
    }

    public function success(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');

            $options = json_decode(base64_decode($state), true);

            $result = Canva::authorizeCustomerFromCode($code, $options);

            if ($result['success']) {
                return '<script>window.close()</script>';
            } else {
                return $result['response'];
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function disconnect(Request $request)
    {
        $data = $request->validate([
            'access_token' => 'required|string',
            'session_id' => 'required|string',
            'user_id' => 'required|numeric',
            'customer_id' => 'nullable|numeric',
        ]);

        Canva::revokeCustomerAccess($data);

        return response()->json(['success' => true]);
    }
}
