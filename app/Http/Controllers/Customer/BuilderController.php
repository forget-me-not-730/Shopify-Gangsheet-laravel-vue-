<?php

namespace App\Http\Controllers\Customer;

use App\GangSheet\Facades\DripApps;
use App\GangSheet\Traits\CanvaDesignController;
use App\Http\Controllers\Controller;
use App\Jobs\UploadFileJob;
use App\Models\Customer;
use App\Models\CustomerImage;
use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use App\Models\UserImage;
use App\Repositories\DesignRepository;
use App\Repositories\GalleryRepository;
use App\Services\SvgService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Symfony\Component\Process\Process;

class BuilderController extends Controller
{
    use CanvaDesignController;

    public function __construct(
        private readonly DesignRepository  $designRepository,
        private readonly GalleryRepository $galleryRepository
    )
    {

    }

    function create($slug, Request $request)
    {
        $size_id = $request->get('size') ?? $request->get('variant');

        $merchant = $request->get('shop');

        $product = Product::with('sizes')
            ->where('user_id', $merchant->id)
            ->where('slug', $slug)
            ->first();

        if ($product && $product->sizes->count()) {

            $customer = auth('customer')->user();

            $customer?->loadDesignsCount();

            if ($size_id) {
                $size = $product->sizes->where('id', $size_id)->first();
            }

            if (empty($size)) {
                $size = $product->sizes[0];
            }

            $props = [
                'id' => Str::uuid()->toString(),
                'product' => $product,
                'size' => $size,
                'merchant' => $merchant,
                'customer' => $customer,
                'quantity' => 1,
            ];

            if ($product->isRollingGangSheet()) {
                return inertia('Customer/RollingGangSheetPage', $props);
            }

            return inertia('Customer/BuilderPage', $props);
        }

        return inertia('Customer/InvalidProduct', [
            'merchant' => $merchant
        ]);
    }

    function design($design_id, Request $request)
    {
        $data = $this->validate($request, [
            'token' => 'nullable|string'
        ]);

        $design = Design::withTrashed()->findOrFail($design_id);

        if ($design->merchant->isWooStore()) {
            return redirect()->route('woo.builder.edit', [
                'design_id' => $design_id,
                'token' => $data['token'] ?? null
            ]);
        }

        $design->load([
            'product' => function ($q) {
                $q->with('sizes');
            },
            'size',
            'merchant',
            'customer',
            'order'
        ]);

        $customer = $design->customer ?? auth('customer')->user();

        if ($customer) {
            $designCount = Design::whereNot('status', Design::STATUS_CREATED)
                ->where('customer_id', $customer->id)
                ->count();

            $customer['designCount'] = $designCount;
        }

        $product = $design->product;
        $size = $design->data['meta']['variant'] ?? $design->size;

        $props = [
            'id' => $design->id,
            'product' => $product,
            'size' => $size,
            'merchant' => $design->merchant,
            'customer' => $customer,
            'order' => $design->order,
            'quantity' => $design->quantity,
            'data' => $design->data,
            'token' => $data['token'] ?? null,
            'editRequest' => $design->edit_request,
            'editMode' => true
        ];

        if ($product?->isRollingGangSheet()) {
            return inertia('Customer/RollingGangSheetPage', $props);
        }

        return inertia('Customer/BuilderPage', $props);
    }

    public function deleteDesign($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        $success = $design->delete();

        return response()->json([
            'success' => $success
        ]);
    }

    public function restoreDesign($design_id)
    {
        $success = Design::withTrashed()
            ->find($design_id)
            ->restore();

        return response()->json([
            'success' => $success,
        ]);
    }

    public function viewDesign($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);
        $designJson = $design->replaceImageUrls($design->data);

        return inertia('DesignViewPage', [
            'designJson' => $designJson
        ]);
    }

    function uploadImage(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'session_id' => 'required|string',
                'customer_id' => 'nullable|integer',
                'image' => 'required|file|max:102400|min:0.5'
            ]);

            if (storage()->exists('uploads')) {
                storage()->makeDirectory('uploads');
            }

            $file = $request->file('image');

            $path = $file->store('uploads', ['disk' => 'public']);
            $localPath = storage()->path($path);

            if (!file_exists($localPath)) {
                throw new \Exception('Unable to upload your image.');
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = Str::uuid()->toString();

            $originFilePath = temp_path("$fileName.$extension");

            try {
                $type = null;

                if ($extension === 'svg') {
                    $xml = SvgService::isValid($localPath);

                    if (!empty($xml['error'])) {
                        throw new \Exception($xml['message']);
                    }

                    spaces()->put("images/$fileName.svg", $xml->asXML());
                } else if ($extension === 'pdf' || $extension === 'ai' || $extension === 'eps') {
                    $remotePath = "images/$fileName.svg";

                    // Convert to SVG using Inkscape;
                    $svgFilePath = storage()->path("uploads/$fileName.svg");
                    $command = "inkscape --file=$localPath -l --pdf-poppler --export-area-drawing -o $svgFilePath";
                    $process = Process::fromShellCommandline($command, timeout: 60);
                    $process->run();

                    if (!$process->isSuccessful()) {
                        throw new \Exception($process->getErrorOutput());
                    }

                    $xml = SvgService::isValid($svgFilePath);

                    unlink($svgFilePath);

                    if (!empty($xml['error'])) {
                        throw new \Exception($xml['message']);
                    }

                    spaces()->put($remotePath, $xml->asXML());
                } else {
                    $image = Image::make($localPath);
                    $hasBackground = $image->hasBackground();

                    if ($extension === 'png' || $extension === 'psd') {
                        $image->trim('transparent');
                        $type = 'trim';
                    }

                    spaces()->put("images/$fileName.png", $image->encode('png'));

                    $resolution = $image->getImageResolution();

                    [$width, $height] = $this->makeThumbImage($image, $fileName);
                }

                $customer = auth('customer')->user();

                $customerImage = CustomerImage::create([
                    'name' => $fileName,
                    'customer_id' => $data['customer_id'] ?? $customer->id ?? null,
                    'session_id' => $data['session_id'],
                    'extension' => $extension,
                    'title' => $file->getClientOriginalName(),
                    'width' => $width ?? null,
                    'height' => $height ?? null,
                    'type' => $type ?? null,
                    'resolution' => $resolution ?? null
                ]);

                UploadFileJob::dispatch($localPath, $originFilePath);

                return response()->json([
                    'success' => true,
                    'image' => $customerImage,
                    'has_background' => $hasBackground ?? false
                ]);
            } catch (\Exception $exception) {

                return response()->json([
                    'success' => false,
                    'error' => $exception->getMessage()
                ]);
            }
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function removeBackgroundAndUpload(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'image_id' => 'required|integer',
            ]);

            $customerImage = CustomerImage::findOrFail($data['image_id']);

            $bgRemovalResponse = DripApps::removeBg([
                'image' => $customerImage->url
            ]);

            if (!$bgRemovalResponse['success']) {
                return response()->json($bgRemovalResponse);
            }

            $image = Image::make($bgRemovalResponse['image']);

            spaces()->put($customerImage->path, $image->encode('png'));
            [$width, $height] = $this->makeThumbImage($image, $customerImage->name);

            $resolution = $image->getImageResolution();

            $customerImage->fill([
                'width' => $width,
                'height' => $height,
                'type' => 'removebg',
                'resolution' => $resolution
            ]);

            $customerImage->save();

            return response()->json([
                'success' => true,
                'image' => $customerImage,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    private function makeThumbImage($image, $fileName)
    {
        $width = $image->getWidth();
        $height = $image->getHeight();

        $resizeWidth = 300;
        if ($width > $resizeWidth) {
            $image->resize($resizeWidth, intval($resizeWidth * $height / $width), function ($constraint) {
                $constraint->aspectRatio();
            });

            $thumbPath = "images/thumbs/{$fileName}.png";
            spaces()->put($thumbPath, $image->encode('png'));
        }

        return [$width, $height];
    }

    public function saveDesign(Request $request)
    {
        $data = $this->validate($request, [
            'design_id' => 'nullable|string',
            'product_id' => 'nullable|numeric',
            'quantity' => 'required|integer',
            'variant_id' => 'required|integer',
            'customer_id' => 'nullable|numeric',
            'session_id' => 'required|string',
            'json' => 'required|array',
            'shop_id' => 'required|integer',
            'thumbnail' => 'required',
            'type' => 'nullable|numeric'
        ]);

        $merchant = $request->get('shop');
        $customer = auth('customer')->user();

        $data['customer_id'] = $data['customer_id'] ?? $customer->id ?? null;
        $data['shop_id'] = $merchant->id;

        $design = $this->designRepository->createOrUpdate($data);

        return response()->json([
            'success' => true,
            'design_id' => $design->id
        ]);
    }

    public function saveGangSheet(Request $request)
    {
        $data = $this->validate($request, [
            'design_id' => 'required|string',
            'gang_sheet' => 'required|string'
        ]);

        $design = Design::find($data['design_id']);

        $gang_sheet = explode(',', $data['gang_sheet'])[1];
        $gang_sheet = base64_decode($gang_sheet);

        $canvas = Image::make($gang_sheet);

        $canvas->pixel([127, 127, 128, 0.01], 0, 0);
        $canvas->getCore()->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
        $canvas->getCore()->setImageResolution(300, 300);

        spaces()->put($design->image_path, $canvas->encode('png'), ['visibility' => 'private']);

        return response()->json([
            'success' => true,
            'design_id' => $design->id
        ]);
    }

    function report(Request $request)
    {
        $data = $this->validate($request, [
            'shop' => 'required|string',
            'message' => 'required|string',
            'product' => 'required|string'
        ]);

        return response()->json();
    }

    function login(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $data['user_id'] = merchant()->id ?? null;

        if (auth('customer')->attempt($data)) {
            return redirect()->back();
        }

        return redirect()->back()->withErrors(['email' => 'These credentials do not match our records.']);
    }

    function register(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:customers',
            'phone' => 'nullable|string',
            'password' => 'required|string|confirmed'
        ]);

        $merchant = $request->get('shop');

        $customer = Customer::updateOrCreate([
            'email' => $data['email'],
            'user_id' => $merchant?->id ?? null,
        ], [

            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
        ]);

        $customer->save();

        auth('customer')->login($customer);

        return redirect()->back();
    }

    function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $customer = Customer::where('email', $request->email)
            ->where('user_id', $request->shop->id)
            ->first();

        if (!$customer) {
            throw ValidationException::withMessages([
                'email' => [__('We can\'t find a user with that email address in this shop.')],
            ]);
        }

        $credentials = array_merge(
            $request->only('email'),
            ['user_id' => $request->shop->id]
        );

        $status = Password::broker('customers')->sendResetLink(
            $credentials,
            function ($user, $token) {
                $user->sendPasswordResetNotification($token);
            }
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    function resetPassword(Request $request, $token)
    {
        return inertia('Auth/CustomerResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    function resetPasswordSave(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        $credentials['user_id'] = $request->shop->id;

        $status = Password::broker('customers')->reset(
            $credentials,
            function ($user) use ($request) {
                if ($user->user_id != $request->shop->id) {
                    throw ValidationException::withMessages([
                        'email' => [__('Invalid shop association')],
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? inertia('Auth/PasswordResetSuccess')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    function logout()
    {
        auth('customer')->logout();

        return response()->json([
            'success' => true
        ]);
    }

    function getMerchantImageCategories(Request $request)
    {
        $merchant = $request->get('shop');
        $shopId = $merchant->getSetting('galleryShareWith', $merchant->id);

        $categories = $this->galleryRepository->getUserGallery($shopId);

        return response()->json($categories);
    }

    function getMerchantImages(Request $request)
    {
        $data = $this->validate($request, [
            'category_id' => 'nullable|integer',
            'search' => 'nullable|string',
            'perPage' => 'nullable|numeric',
            'filter' => 'nullable|string',
            'image_tags' => 'nullable|array',
            'orderBy' => 'nullable|array'
        ]);

        $merchant = $request->get('shop');
        $shopId = $merchant->getSetting('galleryShareWith', $merchant->id);

        $enableColorOverlay = $merchant->getSetting('enableColorOverlay', false);
        $colorOverlayAllowed = $merchant->getSetting('colorOverlayAllowed', 'all');

        if (!$enableColorOverlay) {
            $colorOverlayAllowed = null;
        }

        $query = UserImage::whereNotNull('name')
            ->where('status', 1)
            ->where('user_id', $shopId);

        if (isset($data['search'])) {
            $query->where('title', 'LIKE', '%' . $data['search'] . '%');
        }

        if (!empty($data['filter']) && $data['filter'] !== 'all') {
            $query->where('best_seller', true);
        }

        if ($colorOverlayAllowed === 'category') {
            $query->with('category');
        }

        if (isset($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }

        // Sorting configuration
        $sortMap = [
            'oldest' => ['created_at', 'asc'],
            'newest' => ['created_at', 'desc'],
            'most-used' => ['used_count', 'desc'],
            'least-used' => ['used_count', 'asc'],
            'abc' => ['title', 'asc'],
            'z-a' => ['title', 'desc']
        ];

        if (!empty($data['orderBy'])) {
            foreach ($data['orderBy'] as $sortOption) {
                if (isset($sortMap[$sortOption])) {
                    [$column, $direction] = $sortMap[$sortOption];
                    $query->orderBy($column, $direction);
                }
            }
        }

        $query->orderBy('best_seller', 'desc')
            ->orderBy('order', 'asc');

        $images = $query->latest()
            ->paginate($data['perPage'] ?? 12)
            ->onEachSide(0);

        if ($colorOverlayAllowed) {
            $images->getCollection()->transform(function ($image) use ($colorOverlayAllowed) {
                $image->color_overlay = $colorOverlayAllowed === 'all' ? true : $image->category->color_overlay;
                return $image;
            });
        }

        return response()->json($images);
    }


    public function sendEditRequest(Design $design)
    {
        $design->sendEditRequest();

        return response()->json([
            'success' => true
        ]);
    }

    public function approveEditRequest(Design $design)
    {
        $design->approveEditRequest();

        return response()->json([
            'success' => true,
            'edit_url' => $design->customer_edit_url
        ]);
    }

    public function declineEditRequest(Design $design, Request $request)
    {
        $design->declineEditRequest($request->decline_reason);

        return response()->json([
            'success' => true
        ]);
    }

    public function uploadBase64Image(Request $request)
    {
        try {
            $data = $request->validate([
                'image' => 'required|string',
                'customer_id' => 'nullable|numeric',
                'session_id' => 'required|string',
                'parent_id' => 'nullable|numeric',
                'type' => 'nullable|string',
                'image_name' => 'nullable|string',
            ]);

            $imageData = explode(',', $data['image'])[1];
            $image = base64_decode($imageData);

            $fileName = Str::uuid();
            $fileTitle = $fileName;
            $path = "images/$fileName.png";

            spaces()->put($path, $image);

            $image = Image::make($image);

            if ($data['parent_id'] ?? null) {
                $parentImage = CustomerImage::find($data['parent_id']);

                if (!$parentImage && ($data['image_name'] ?? null)) {
                    $parentImage = CustomerImage::where('name', $data['image_name'])->first();
                }

                if ($parentImage) {
                    $fileTitle = $parentImage->title . ($data['type'] ?? '');
                    if (!empty($parentImage->resolution)) {
                        $image->setResolution($parentImage->resolution);
                    }
                }
            }

            [$width, $height] = $this->makeThumbImage($image, $fileName);

            $customer = auth('customer')->user();

            $uploadImage = CustomerImage::create([
                'customer_id' => $customer->id ?? $data['customer_id'],
                'session_id' => $data['session_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'] ?? null,
                'extension' => 'png',
                'title' => $fileTitle,
                'name' => $fileName,
                'width' => $width,
                'height' => $height,
                'resolution' => $parentImage->resolution ?? null
            ]);

            return response()->json([
                'success' => true,
                'image' => $uploadImage
            ]);
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    function getShopDesign($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        return response()->json([
            'success' => true,
            'design' => $design,
        ]);
    }

    public function deleteCustomerImages(Request $request)
    {
        CustomerImage::destroy($request->image_ids ?? []);

        return response()->json([
            'success' => true
        ]);
    }

    public function getCustomerImages(Request $request)
    {
        try {
            $data = $request->validate([
                'customer_id' => 'nullable|numeric',
                'session_id' => 'required|string',
                'page' => 'nullable|integer',
                'search' => 'nullable|string'
            ]);

            $customerId = $data['customer_id'] ?? null;

            $sessionId = $data['session_id'];

            $query = CustomerImage::select([
                'id',
                'name',
                'title',
                'parent_id',
                'width',
                'height',
                'extension',
                'resolution'
            ])->where(function ($q) use ($customerId, $sessionId) {
                $q->where('session_id', $sessionId);

                if (!empty($customerId)) {
                    $q->orWhere('customer_id', $customerId);
                }
            });

            if (!empty($data['search'])) {
                $query->where('title', 'like', "%{$data['search']}%");
            }

            $images = $query->latest()
                ->paginate(12)
                ->onEachSide(0);

            return response()->json($images);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function getCustomerDesigns(Request $request)
    {
        try {
            $data = $request->validate([
                'shop_id' => 'nullable|numeric',
                'customer_id' => 'nullable|numeric',
                'product_id' => 'nullable|numeric',
                'woo_product_id' => 'nullable|numeric',
                'variant_id' => 'required|string',
                'session_id' => 'required|string',
                'page' => 'nullable|integer',
                'search' => 'nullable|string',
                'trashed' => 'nullable|boolean'
            ]);

            $customerId = $data['customer_id'] ?? null;

            $sessionId = $data['session_id'];

            $query = Design::with('merchant:id,slug');

            if ($data['trashed'] ?? false) {
                $query->onlyTrashed();
            }

            $query = $query->select(['id', 'user_id', 'size_id', 'name', 'status', 'created_at']);

            if ($data['shop_id'] ?? null) {
                $query->where('user_id', $data['shop_id']);
            }

            $query->where(function ($query) use ($data) {
                if ($data['product_id'] ?? null) {
                    $query->where('product_id', $data['product_id']);
                }

                if ($data['woo_product_id'] ?? null) {
                    $query->orWhere('product_id', $data['woo_product_id']);
                }
            });

            if ($data['variant_id'] !== 'all') {
                $query->where('size_id', $data['variant_id']);
            }

            $query->where(function ($query) use ($customerId, $sessionId) {
                $query->where('session_id', $sessionId);

                if (!empty($customerId)) {
                    $query->orWhere('customer_id', $customerId);
                }
            })
                ->whereNotNull('name');

            if (!empty($data['search'])) {
                $query->where('name', 'LIKE', "%" . $data['search'] . "%");
            }

            $designs = $query->latest()
                ->paginate(8)
                ->onEachSide(0);

            return response()->json($designs);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function getCustomer(Request $request, $customer_id)
    {
        $userId = $request->get('user_id');
        $customerEmail = $request->get('customer_email');

        $query = Customer::where('user_id', $userId);

        if ($customerEmail) {
            $customer = $query->where('email', $customerEmail)->first();
        }

        if (empty($customer)) {
            $shop = User::find($userId);

            if ($shop->isWooStore()) {
                $customer = $query->where('wc_user_id', $customer_id)->first();

                if (empty($customer)) {
                    $customer = $shop->pullCustomer($customer_id);
                }
            } else {
                $customer = $query->where('id', $customer_id)->first();
            }
        }

        if (empty($customer)) {
            return response()->json([
                'success' => false,
                'error' => 'Unable to find the customer.'
            ]);
        }

        $customer->loadDesignsCount();

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }


    public function saveDraftDesign(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'design_id' => 'nullable|string',
                'product_id' => 'nullable|numeric',
                'variant_id' => 'required|integer',
                'customer_id' => 'nullable|numeric',
                'session_id' => 'required|string',
                'json' => 'required|array',
                'shop_id' => 'required|integer',
                'thumbnail' => 'required|string'
            ]);

            $design_id = $data['design_id'] ?? null;
            $customerId = $data['customer_id'] ?? null;

            if (empty($design_id) || Design::where('id', $design_id)->whereNotNull('order_id')->count() > 0) {
                $design_id = Str::uuid()->toString();
            }

            if (empty($design_id)) {
                $design_id = Str::uuid()->toString();
            }

            $newDesign = [
                'id' => $design_id,
                'ip_address' => $request->getClientIp(),
                'user_id' => $data['shop_id'],
                'product_id' => $data['product_id'],
                'size_id' => $data['variant_id'],
                'session_id' => $data['session_id'],
                'quantity' => 0,
                'customer_id' => $customerId,
                'status' => Design::STATUS_DRAFT,
                'data' => $data['json'],
                'name' => $data['json']['name'] ?? 'New Gang Sheet'
            ];

            $design = Design::findOrNew($design_id);
            $design->fill($newDesign);
            $design->save();

            $thumbnail = explode(',', $data['thumbnail'])[1];
            $thumbnail = base64_decode($thumbnail);

            Storage::disk('spaces')->put($design->thumbnail_path, $thumbnail);

            return response()->json([
                'success' => true,
                'design_id' => $design_id
            ]);
        } catch (\Exception $exception) {

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
