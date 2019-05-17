<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DesignController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage') ?? 10;
        $designId = $request->get('id');
        $userId = $request->get('user_id') ?? null;
        $order_id = $request->get('order_id') ?? null;
        $customer = $request->get('customer') ?? null;
        $generation_method = $request->get('generation_method');
        $status = $request->get('status', 'all');
        $tags = $request->get('tags');
        $from = $request->get('from');
        $to = $request->get('to');
        $type = $request->get('type', 'all');

        $query = Design::select(['id', 'user_id', 'product_id', 'customer_id', 'quantity', 'order_id', 'file_name', 'type', 'data', 'status', 'created_at'])
            ->with([
                'user:id,name,type,website,company_name',
                'order:id,wc_order_id,name,email,created_at',
                'customer:id,name,email',
                'metaData'
            ]);

        if (!empty($designId)) {
            $query->where('id', $designId);
        } else {
            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            if (!empty($order_id)) {
                $query->whereHas('order', function ($q) use ($order_id) {
                    $q->where('id', $order_id)
                        ->orWhere('wc_order_id', $order_id);
                });
            }

            if (!empty($customer)) {
                $customer = trim($customer);

                $query->where(function ($q) use ($customer) {
                    $q->whereHas('customer', function ($q) use ($customer) {
                        $q->where('name', 'like', "%$customer%")
                            ->orWhere('email', 'like', "%$customer%");
                    })->orWhereHas('order', function ($q) use ($customer) {
                        $q->where('name', 'like', "%$customer%")
                            ->orWhere('email', 'like', "%$customer%");
                    });
                });
            }

            if ($status !== 'all') {
                if ($status === 'processing') {
                    $query->whereIn('status', ['processing', 'pending']);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($type !== 'all') {
                $query->where('type', $type);
            }

            if (!empty($tags)) {
                $tags = explode(',', $tags);
                $query->whereHas('metaData', function ($q) use ($tags) {
                    $q->whereIn('key', $tags);
                });
            }

            if ($generation_method) {
                $query->whereHas('metaData', function ($q) use ($generation_method) {
                    $q->where('key', 'generation_method')->where('value', $generation_method);
                });
            }
        }

        if ($from) {
            $query->where('updated_at', '>=', $from);
        }

        if ($to) {
            $query->where('updated_at', '<=', $to);
        }

        $designs = $query->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $designs->getCollection()->withoutAppends();

        $users = User::select('id', 'website', 'company_name')
            ->where(function ($query) {
                $query->whereNotNull('website')
                    ->orWhereNotNull('company_name');
            })->get();

        return Inertia::render('Admin/Designs', [
            'designs' => $designs,
            'users' => $users,
            'filter' => [
                'design_id' => $designId,
                'order_id' => $order_id,
                'customer' => $customer,
                'status' => $status,
                'generation_method' => $generation_method,
                'user_id' => $userId,
                'tags' => $tags,
                'type' => $type
            ]
        ]);
    }

    public function rebuild($design_id, Request $request)
    {
        $method = $request->input('method', 'inkscape');
        $design = Design::withTrashed()->findOrFail($design_id);

        $design->setMetaData([
            'generation_method' => $method,
            'generation_start' => time()
        ]);

        $design->generateGangSheetImage($method);

        return response()->json([
            'success' => true,
            'meta_data' => $design->metaData
        ]);
    }

    public function open($design_id)
    {
        $design = Design::withTrashed()->find($design_id);

        $sheet_path = $design->image_path;
        if (spaces()->exists($sheet_path)) {
            $expires = now()->addMinutes(10);
            $fileUrl = spaces()->temporaryUrl($sheet_path, $expires, [
                'url' => config('filesystems.disks.spaces.url')
            ]);

            return redirect()->to($fileUrl);
        }

        return 'Image is still being generated.';
    }

    public function preview($design_id)
    {
        $design = Design::withTrashed()->find($design_id);

        return redirect()->to($design->thumbnail_url);
    }

    public function addNote(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'memo' => 'nullable|string',
            'has_problem' => 'nullable|boolean',
            'status' => 'nullable|string'
        ]);

        $design = Design::withTrashed()->findOrFail($data['design_id']);

        $design->setMetaData('memo', $data['memo']);
        $design->setMetaData('has_problem', $data['has_problem'] ? true : null);

        if (!empty($data['status'])) {
            $design->status = $data['status'];
            $design->save();
        }

        $design->load('metaData');

        return response()->json([
            'success' => true,
            'meta_data' => $design->metaData
        ]);
    }
    public function getLog(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'required|exists:designs,id'
        ]);

        $design = Design::withTrashed()->findOrFail($data['design_id']);

        $log = $design->getLog();
        return response()->json([
            'success' => true,
            'log_file_path' => $log['log_file_path'],
            'log_content' => $log['log_content'],
            'status' => $design->status,
        ]);
    }

    public function clearLog(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'required|exists:designs,id'
        ]);

        $design = Design::withTrashed()->findOrFail($data['design_id']);
        $design->clearLog();

        return response()->json([
            'success' => true
        ]);
    }
}
