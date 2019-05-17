<?php

namespace App\Http\Controllers\Gsb;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Design;
use App\Models\User;
use App\Repositories\DesignRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuilderController extends Controller
{
    public function __construct(private readonly DesignRepository $designRepository)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'nullable|string',
        ]);

        $shop = $request->get('shop');

        if (!empty($data['design_id'])) {

            $design = Design::withTrashed()->where([
                'user_id' => $shop->id,
                'id' => $data['design_id']
            ])->first();
        }

        return inertia('Gsb/BuilderPage', [
            'shop' => $shop,
            'design' => $design ?? null
        ]);
    }

    public function saveDesign(Request $request)
    {
        $data = $this->validate($request, [
            'shop_id' => 'required',
            'design_id' => 'nullable|string',
            'product_id' => 'nullable|numeric',
            'variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer',
            'session_id' => 'nullable|string',
            'customer_id' => 'nullable|numeric',
            'json' => 'required|array',
            'thumbnail' => 'required|string'
        ]);

        $merchant = $request->get('shop');

        $data['shop_id'] = $merchant->id;

        $design = $this->designRepository->createOrUpdate($data);

        return response()->json([
            'success' => true,
            'design' => [
                'id' => $design->id,
                'name' => $design->name,
                'quantity' => $design->quantity,
                'status' => $design->status,
                'thumbnail_url' => $design->thumbnail_url,
                'download_url' => route('gs.gang-sheet', $design->id . '.png')
            ]
        ]);
    }

    public function editDesign(Request $request)
    {
        $data = $request->validate([
            'shop_id' => 'required|uuid',
            'design_id' => 'required|uuid'
        ]);

        $shop = User::where('shop_uuid', $data['shop_id'])->first();
        $design = Design::withTrashed()->find($data['design_id']);

        return inertia('Gsb/EditPage', [
            'shop' => $shop,
            'design' => $design,
        ]);
    }

    public function getGangSheet($fileName)
    {
        $designId = explode('.', basename($fileName))[0];
        $design = Design::where(['id' => $designId])->first();

        if ($design) {

            if (Storage::disk('spaces')->exists($design->image_path)) {
                return redirect()->to($design->image_url);
            }

            return view('errors.design.processing');
        }

        return view('errors.design.not-found');
    }

    public function saveCustomer(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'email' => 'nullable|email',
                'name' => 'nullable|string',
            ]);

            $customer = Customer::updateOrCreate([
                'user_id' => $data['user_id'],
                'wc_user_id' => $data['id'],
            ], [
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'password' => isset($data['name']) ? bcrypt($data['name']) : null,
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to save customer',
                'message' => $th->getMessage()
            ]);
        }
    }
}
