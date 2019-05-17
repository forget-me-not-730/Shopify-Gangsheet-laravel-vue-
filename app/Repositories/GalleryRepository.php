<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Models\UserImage;
use App\Models\UserImageCategory;
use Illuminate\Support\Facades\DB;

class GalleryRepository extends BaseRepository
{
    public function getUserGallery($userId): object
    {
        return UserImageCategory::select(['id', 'user_id', 'parent_id', 'color_overlay', 'name', 'status', 'created_at', 'order'])
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->select(['id', 'user_id', 'parent_id', 'color_overlay', 'name', 'status', 'created_at', 'order'])
                    ->orderBy('order')
                    ->withCount('images')
                    ->latest();
            }])
            ->orderBy('order')
            ->withCount('images')
            ->latest()
            ->get();
    }

    public function getUserTags($userId): object
    {
        return Tag::select(['id', 'name'])
            ->where('user_id', $userId)
            ->where('model', UserImage::class)
            ->withCount('userImages')
            ->get();
    }

    public function addUserTags($userId, $tagNames = [], $imageIds = [], $categoryIds = []): void
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate([
                'model' => UserImage::class,
                'name' => $tagName,
                'user_id' => $userId
            ]);
            $tagIds[] = $tag->id;
        }

        if (!empty($categoryIds)) {
            $categoryImageIds = UserImage::whereIn('category_id', $categoryIds)->pluck('id')->toArray();
        }

        $allImageIds = array_merge($imageIds, $categoryImageIds ?? []);

        $uniqueImageIds = array_unique($allImageIds);

        $updateData = [];

        foreach ($tagIds as $tagId) {
            foreach ($uniqueImageIds as $imageId) {
                $updateData[] = [
                    'user_image_id' => $imageId,
                    'tag_id' => $tagId
                ];
            }
        }

        DB::table('user_image_tags')->upsert($updateData, ['user_image_id', 'tag_id'], ['user_image_id', 'tag_id']);
    }

    public function deleteUserTags($tagIds = []): void
    {
        if (!empty($tagIds)) {
            Tag::whereIn('id', $tagIds)->delete();
        }
    }

    public function move($categoryId, $imageIds = [], $categoryIds = []): void
    {
        if (!empty($imageIds)) {
            UserImage::whereIn('id', $imageIds)->update(['category_id' => $categoryId]);
        }

        if (!empty($categoryIds)) {
            UserImageCategory::whereIn('id', $categoryIds)->update(['parent_id' => $categoryId]);
        }
    }

    public function updateStatus($status, $imageIds = [], $categoryIds = []): void
    {
        if (!empty($imageIds)) {
            UserImage::whereIn('id', $imageIds)->update(['status' => $status === 'active']);
        }

        if (!empty($categoryIds)) {
            UserImageCategory::whereIn('id', $categoryIds)->update(['status' => $status === 'active']);
        }
    }
}
