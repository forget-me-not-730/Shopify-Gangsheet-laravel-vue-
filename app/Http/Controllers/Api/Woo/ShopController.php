<?php

namespace App\Http\Controllers\Api\Woo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function getRandomEmail(): string
    {
        $email = Str::random(5) . '@buildagangsheet.com';

        if (User::where('email', $email)->exists()) {
            return $this->getRandomEmail();
        }

        return $email;
    }

    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|string',
                'username' => 'required|string',
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'shop_name' => 'string|required',
                'website' => 'string|required',
                'uuid' => 'string|required'
            ]);

            $newShop = [
                'type' => 'woo',
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'first_name' => $data['first_name'] ?? $data['username'],
                'last_name' => $data['last_name'],
                'company_name' => $data['shop_name']
            ];

            $shop = User::where('website', $data['website'])
                ->where('shop_uuid', $data['uuid'])
                ->withTrashed()
                ->first();

            if ($shop) {
                $shop->restore();
            } else {
                $shop = new User();
                $password = Str::random(12);

                if (User::where('email', $data['email'])->exists()) {
                    $newShop['email'] = $this->getRandomEmail();
                } else {
                    $newShop['email'] = $data['email'];
                }

                $newShop['password'] = Hash::make($password);
                $newShop['website'] = $data['website'];
                $newShop['shop_uuid'] = Str::uuid()->toString();
            }

            $shop->fill($newShop);

            // Verify Registration
            $state = Str::random(40);
            $res = $shop->wooApi('post', 'verify', ['state' => $state], false);

            if (!$res) {
                return response()->json([
                    'success' => false,
                    'error' => 'We are not able to verify your shop. Please try again later or contact the support.'
                ], 400);
            }

            $shop->save();
            $shop->addLog('Shop Registered', $data);

            if (empty($res['error'])) {
                $shop->addLog('Shop Registration Verify', $res);
                if ($state != $res['state']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid Request.'
                    ], 400);
                }
            } else {
                $shop->addLog('Shop Registration Error', $res['error']);
                return response()->json([
                    'success' => false,
                    'error' => $res['error']
                ], 500);
            }

            $shop->setSetting('shop_email', $data['email']);

            $plainTextToken = $shop->getPlainTextToken();

            event(new Registered($shop));

            $shop['access_token'] = $plainTextToken;

            return response()->json([
                'success' => true,
                'shop' => $shop,
                'gs_latest_version' => option('gs_latest_version', '1.1.0')
            ]);

        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function delete(Request $request)
    {
        try {
            $shop = $request->user();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function shop(Request $request)
    {
        try {

            return response()->json([
                'success' => true,
                'shop' => $request->user(),
                'gs_latest_version' => option('gs_latest_version', '1.1.0')
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ], 500);
        }

    }

    public function updateShopSettings(Request $request)
    {
        try {

            $data = $request->validate([
                'name' => 'required|string',
                'logo' => 'nullable|string',
                'show_gallery' => 'required|boolean',
                'gangSheetFileName' => 'nullable|string',
                'printFileName' => 'nullable|boolean',
                'file_types' => 'required|array',
            ]);

            $shop = $request->user();
            $shop->company_name = $data['name'];

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('logos/' . $shop->id);
                $shop->logo = $path;
            }

            $shop->show_gallery = $data['show_gallery'];

            $shop->save();

            $shop->setSetting([
                'file_types' => $data['file_types'] ?? [],
                'googleDriveFolderName' => $data['googleDriveFolderName'] ?? 'gang_sheets',
                'gangSheetFileName' => $data['gangSheetFileName'] ?? config('custom.default_file_name'),
                'printFileName' => $data['printFileName'] ?? false,
            ]);

            if (isset($data['logo'])) {
                $shop->updateLogo($data['logo']);
            }

            return response()->json([
                'success' => true,
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }

    }

    public function addCredit(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'amount' => 'required|numeric|min_digits:1'
            ]);

            $shop = $request->user();

            $url = $shop->getStripeCheckoutUrl(
                $data['amount'],
                "{$shop->website}/wp-admin/admin.php?page=gang-sheet",
                "{$shop->website}/wp-admin/admin.php?page=gang-sheet"
            );

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $exception) {
            info($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function report(Request $request)
    {
        $shop = $request->user();

        if (!$shop->getSetting('disableReport', false)) {
            slack_report([
                '*Shop*' . $shop->toString(),
                $request->message
            ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function loginMagicLink(Request $request)
    {
        $shop = $request->user();

        $url = URL::temporarySignedRoute(
            'merchant.login',
            now()->addMinutes(1),
            [
                'user_id' => $shop->id,
                'page_url' => $request->get('page_url') ?? '/merchant/dashboard'
            ]
        );

        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }
}
