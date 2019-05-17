<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserImage;
use App\Models\UserImageTag;
use App\Models\UserImageCategory;
use App\Repositories\GalleryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as Image;
use App\Services\SvgService;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class ImageController extends Controller
{
    public function __construct(private readonly GalleryRepository $galleryRepository)
    {
    }

    function index(Request $request)
    {
        $shop = auth()->user();

        $galleryShareWith = $shop->getSetting('galleryShareWith');
        if ($galleryShareWith) {
            $galleryStore = User::find($galleryShareWith);
            if ($galleryStore) {
                return inertia('Merchant/GallerySharingPage', [
                    'galleryStore' => $galleryStore
                ]);
            }
        }

        $galley = $this->galleryRepository->getUserGallery(auth()->user()->id);
        $tags = $this->galleryRepository->getUserTags(auth()->user()->id);

        return inertia('Merchant/GalleryPage', [
            'gallery' => $galley,
            'tags' => $tags
        ]);
    }

    public function updateStatus(Request $request)
    {
        $data = $this->validate(request(), [
            'image_ids' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'status' => 'required|string'
        ]);

        $this->galleryRepository->updateStatus($data['status'], $data['image_ids'], $data['category_ids']);

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteImages(Request $request)
    {
        try {
            $data = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'required'
            ]);

            $imageIds = $data['ids'];

            UserImage::whereIn('id', $imageIds)->delete();

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


    public function getCategoryImages(Request $request)
    {
        $data = $this->validate(request(), [
            'user_id' => 'required|integer',
            'category_id' => 'required|integer',
            'status' => 'nullable|integer',
            'orderBy' => 'nullable|array'
        ]);

        $perPage = $request->get('per_page', 30);

        $query = UserImage::where('user_id', $data['user_id'])
            ->where('category_id', $data['category_id']);

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
            
        $query->orderBy('order');

        if (($data['status'] ?? 2) != 2) {
            $query->where('status', boolval($data['status']));
        }

        $images = $query->with('tags')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'images' => $images->items(),
            'has_more' => $images->hasMorePages(),
        ]);
    }

    public function getTagImages(Request $request)
    {
        $data = $this->validate(request(), [
            'user_id' => 'required|integer',
            'tag_id' => 'required|integer',
            'status' => 'nullable|integer'
        ]);

        $perPage = $request->get('per_page', 30);

        $query = UserImage::where('user_id', $data['user_id'])
            ->whereHas('tags', function ($query) use ($data) {
                $query->where('tags.id', $data['tag_id']);
            });

        if (($data['status'] ?? 2) != 2) {
            $query->where('status', boolval($data['status']));
        }

        $images = $query->with(['tags', 'category'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'images' => $images->items(),
            'has_more' => $images->hasMorePages(),
        ]);
    }

    public function reload(Request $request)
    {
        $data = $this->validate($request, [
            'user_id' => 'required|integer'
        ]);

        $tags = $this->galleryRepository->getUserTags($data['user_id']);
        $gallery = $this->galleryRepository->getUserGallery($data['user_id']);

        return response()->json([
            'success' => true,
            'tags' => $tags,
            'gallery' => $gallery
        ]);
    }

    public function getUserTags(Request $request)
    {
        $data = $this->validate($request, [
            'user_id' => 'required|integer',
        ]);

        $tags = $this->galleryRepository->getUserTags($data['user_id']);

        return response()->json([
            'success' => true,
            'tags' => $tags
        ]);
    }

    public function getGallery(Request $request)
    {
        $data = $this->validate($request, [
            'user_id' => 'required|integer'
        ]);

        $gallery = $this->galleryRepository->getUserGallery($data['user_id']);

        return response()->json([
            'success' => true,
            'gallery' => $gallery
        ]);
    }

    public function search(Request $request)
    {
        $data = $this->validate($request, [
            'user_id' => 'required|integer',
            'search' => 'required|string',
            'status' => 'nullable|integer',
        ]);

        $perPage = $request->get('per_page', 30);

        $query = UserImage::where('user_id', $data['user_id']);

        if (!empty($data['search'])) {
            $query->where('title', 'LIKE', '%' . $data['search'] . '%');
        }

        if (($data['status'] ?? 2) != 2) {
            $query->where('status', boolval($data['status']));
        }

        $images = $query->with(['tags', 'category'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'images' => $images
        ]);
    }

    public function addImageTags(Request $request)
    {
        try {
            $data = $request->validate([
                'image_ids' => 'nullable|array',
                'category_ids' => 'nullable|array',
                'tag_names' => 'required|array'
            ]);

            $user = $request->user();

            $this->galleryRepository->addUserTags($user->id, $data['tag_names'], $data['image_ids'], $data['category_ids']);

            $tags = $this->galleryRepository->getUserTags($user->id);
            $gallery = $this->galleryRepository->getUserGallery($user->id);

            return response()->json([
                'success' => true,
                'tags' => $tags,
                'gallery' => $gallery
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function removeTags(Request $request)
    {
        try {
            $data = $request->validate([
                'image_ids' => 'required|array',
                'image_ids.*' => 'required|numeric'
            ]);

            $imageIds = $data['image_ids'];

            UserImageTag::whereIn('user_image_id', $imageIds)
                ->delete();

            $tags = $this->galleryRepository->getUserTags($request->user()->id);

            return response()->json([
                'success' => true,
                'tags' => $tags
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function reorderImageCategory(Request $request)
    {
        try {
            $data = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'required|integer',
            ]);

            $categoryIds = $data['ids'];

            // Update order based on the received IDs
            foreach ($categoryIds as $index => $categoryId) {
                UserImageCategory::where('id', $categoryId)->update(['order' => $index]);
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

    public function createImageCategory(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'image' => 'nullable|string',
            'name' => 'required|string',
            'color_overlay' => 'nullable|boolean'
        ]);

        if (isset($data['id'])) {
            $category = UserImageCategory::findOrFail($data['id']);
        } else {
            $category = new UserImageCategory();
        }

        $category->fill([
            'name' => $data['name'],
            'parent_id' => $data['category_id'] ?? null,
            'user_id' => $request->user()->id,
            'status' => true,
            'order' => 999,
            'color_overlay' => $data['color_overlay'] ?? false
        ]);

        $category->save();

        if ($request->has('image') && isset($data['image'])) {

            $imageData = explode(',', $data['image'])[1];
            $image = base64_decode($imageData);

            try {
                $imageName = $category->id . '.png';
                $filePath = "gallery/{$request->user()->id}/categories/$imageName";

                spaces()->put($filePath, $image);
            } catch (\Exception $exception) {
                report($exception);

                return response()->json([
                    'success' => false,
                    'error' => 'Unable to upload image.'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }

    public function updateImageCategory(Request $request, $category_id)
    {
        try {
            $data = $this->validate($request, [
                'name' => 'required|string',
                'image' => 'nullable|string',
                'hasOldImage' => 'nullable|boolean',
                'color_overlay' => 'nullable|boolean'
            ]);

            UserImageCategory::where('id', $category_id)
                ->update([
                    'name' => $data['name'],
                    'color_overlay' => $data['color_overlay'] ?? false
                ]);

            if ($request->has('image') && isset($data['image'])) {
                $imageData = explode(',', $data['image'])[1];
                $image = base64_decode($imageData);
                
                try {
                    $imageName = $category_id.'.png';
                    $filePath = "gallery/{$request->user()->id}/categories/$imageName";
    
                    spaces()->put($filePath, $image);
                } catch (\Exception $exception) {
                    report($exception);

                    return response()->json([
                        'success' => false,
                        'error' => 'Unable to upload image.'
                    ]);
                }
            }
            
            $originFilePath = "gallery/{$request->user()->id}/categories/$category_id.png";
            // when user deleted category image
            if ($request->has('image') && !isset($data['image']) && $data['hasOldImage']) {
                if(spaces()->exists($originFilePath))
                    spaces()->delete($originFilePath);
            }

            return response()->json([
                'success' => true,
                'image_url' => spaces()->url($originFilePath)
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function deleteImageCategory($category_id)
    {
        try {
            UserImageCategory::where('id', $category_id)->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function updateImageTitle(Request $request, $id)
    {
        try {
            $data = $this->validate($request, [
                'title' => 'required|string'
            ]);

            UserImage::where('id', $id)
                ->update(['title' => $data['title']]);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function generateWatermark(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'image_id' => 'required|numeric',
            ]);

            $image = UserImage::findOrFail($data['image_id']);

            $image->generateWatermarkImage();

            return response()->json([
                'success' => true,
                'name' => $image->name
            ]);
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function creatTagsAndCategory(Request $request)
    {
        $data = $this->validate($request, [
            'tag_names' => 'nullable|array',
            'category_name' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        $category = UserImageCategory::firstOrCreate([
            'name' => $data['category_name'],
            'user_id' => $data['user_id']
        ]);

        $tagIds = [];
        foreach ($data['tag_names'] as $tag) {
            $tag = Tag::updateOrCreate([
                'user_id' => $data['user_id'],
                'model' => UserImage::class,
                'name' => substr(trim($tag), 0, 255)
            ]);
            $tagIds[] = $tag->id;
        }

        return response()->json([
            'success' => true,
            'tag_ids' => $tagIds,
            'category' => $category
        ]);
    }

    public function deleteEmptyTags()
    {
        $data = $this->validate(request(), [
            'tag_ids' => 'nullable|array'
        ]);

        $this->galleryRepository->deleteUserTags($data['tag_ids']);

        $tags = $this->galleryRepository->getUserTags(auth()->user()->id);

        return response()->json([
            'success' => true,
            'tags' => $tags
        ]);
    }

    public function uploadImage(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'category_id' => 'required|numeric',
                'file' => 'required_without:id|max:102400',
                'user_id' => 'required|integer',
                'tag_ids' => 'nullable|string'
            ]);

            if ($request->has('file')) {
                $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

                if ($receiver->isUploaded() === false) {
                    throw new \Exception('File is not uploaded');
                }

                $fileReceived = $receiver->receive();

                if ($fileReceived->isFinished()) {

                    $file = $fileReceived->getFile();
                    $filePath = $file->getRealPath();
                    $mimeType = $file->getMimeType();
                    $imageOriginalName = Str::uuid()->toString();

                    if (!$filePath || !file_exists($filePath)) {
                        throw new \Exception('File not found');
                    }

                    try {

                        if ($mimeType === 'image/svg+xml') {
                            $imageOriginalName .= '.svg';
                            $originalFilePath = "gallery/{$data['user_id']}/raw/$imageOriginalName";

                            $xml = SvgService::trimSVG($filePath);

                            if (!empty($xml['error'])) {
                                throw new \Exception($xml['message']);
                            }

                            spaces()->put($originalFilePath, $xml->asXML());
                        } else {
                            $imageOriginalName .= '.png';
                            $originalFilePath = "gallery/{$data['user_id']}/raw/$imageOriginalName";

                            $newImage = Image::make($filePath);
                            $newImage->trim('transparent');

                            $imageWidth = $newImage->width();
                            $imageHeight = $newImage->height();

                            spaces()->put($originalFilePath, $newImage->encode('png'));
                        }

                        $imageTitle = $file->getClientOriginalName();

                        $image = UserImage::create([
                            'title' => $imageTitle,
                            'user_id' => $data['user_id'],
                            'category_id' => $data['category_id'],
                            'original_name' => $imageOriginalName,
                            'status' => true,
                            'best_seller' => false,
                            'mime_type' => $mimeType ?? null,
                            'width' => $imageWidth ?? null,
                            'height' => $imageHeight ?? null,
                            'order' => 9999
                        ]);

                        if (!empty($data['tag_ids'])) {
                            $tagIds = explode(',', $data['tag_ids']);
                            $image->tags()->sync($tagIds);
                        }

                        $image->generateWatermarkImage();

                        $image->load('tags');

                        return response()->json([
                            'success' => true,
                            'image' => $image
                        ]);

                    } catch (\Exception $exception) {
                        report($exception);

                        return response()->json([
                            'success' => false,
                            'error' => 'Unable to upload image.'
                        ]);
                    } finally {
                        unlink($filePath);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'File is being uploaded'
                    ]);
                }
            }
        } catch (\Exception $exception) {

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function updateImage(Request $request)
    {
        try {
            $data = $request->validate([
                'image_ids' => 'required|array',
                'image_ids.*' => 'required|numeric',
                'best_seller' => 'nullable|boolean'
            ]);

            $imageIds = $data['image_ids'];

            UserImage::whereIn('id', $imageIds)
                ->update(['best_seller' => $data['best_seller']]);

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

    public function moveGallery()
    {
        $data = $this->validate(request(), [
            'user_id' => 'required|integer',
            'image_ids' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'category_id' => 'required|integer'
        ]);

        $this->galleryRepository->move($data['category_id'], $data['image_ids'], $data['category_ids']);

        $gallery = $this->galleryRepository->getUserGallery($data['user_id']);

        return response()->json([
            'success' => true,
            'gallery' => $gallery
        ]);
    }

    public function reorderImages(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        $cases = [];
        foreach ($data['ids'] as $index => $id) {
            $cases[] = "WHEN id = $id THEN $index";
        }
        $cases = implode(' ', $cases);

        UserImage::whereIn('id', $data['ids'])
            ->update(['order' => \DB::raw("(CASE $cases END)")]);

        return response()->json(['success' => true]);
    }
}
