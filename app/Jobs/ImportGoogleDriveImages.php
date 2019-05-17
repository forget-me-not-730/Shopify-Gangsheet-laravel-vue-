<?php

namespace App\Jobs;

use App\Enums\MimeType;
use App\Models\User;
use App\Models\UserImage;
use App\Models\UserImageCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImportGoogleDriveImages extends BaseJob
{


    public int $timeout = 360000;

    public int $tries = 0;

    public int $retryAfter = 361000;

    private $gService;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $folderId, public string $userId, public bool $sharedOnly = false)
    {
        //
    }

    private function createSubCategories($folderId, $categoryId): void
    {
        $subCategoryFolders = $this->gService->files->listFiles([
            'fields' => 'files(id,name,shared)',
            'q' => "'$folderId' in parents and mimeType = 'application/vnd.google-apps.folder'",
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'pageSize' => 1000
        ])->getFiles();

        if (!empty($subCategoryFolders)) {
            $subCategories = [];

            foreach ($subCategoryFolders as $folder) {
                if ($this->sharedOnly && !$folder->getShared()) {
                    continue;
                }

                $subCategories[] = [
                    'folderId' => $folder->getId(),
                    'name' => $folder->getName()
                ];
            }

            foreach ($subCategories as $subcategory) {
                $imageCategory = UserImageCategory::updateOrCreate([
                    'name' => $subcategory['name'],
                    'user_id' => $this->userId,
                    'parent_id' => $categoryId
                ]);

                try {
                    $this->importImage($subcategory['folderId'], $imageCategory->id);
                } catch (\Exception $exception) {
                    printf("Error in processing subcategory: %s\n", $subcategory['name']);
                }
            }

        }
    }

    private function importImage($folderId, $categoryId): void
    {
        $categoryFiles = $this->gService->files->listFiles([
            'fields' => 'files(id,name,mimeType,shared)',
            'q' => "'$folderId' in parents and mimeType != 'application/vnd.google-apps.folder'",
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'pageSize' => 1000
        ])->getFiles();

        foreach ($categoryFiles as $file) {
            $fileId = $file->getId();
            $fileName = $file->getName();
            $mimeType = $file->getMimeType();

            try {
                if (UserImage::where('category_id', $categoryId)->where('title', $fileName)->exists()) {
                    continue;
                }

                $client = $this->gService->getClient();
                $client->fetchAccessTokenWithRefreshToken();

                $response = $this->gService->files->get($fileId, ['alt' => 'media']);
                if ($response->getStatusCode() == 200) {
                    printf("Image processing: %s\n", $fileName);

                    $content = $response->getBody()->getContents();

                    $imageName = Str::uuid();

                    switch ($mimeType) {
                        case MimeType::PNG->value:
                        {
                            $image = Image::make($content)->trim();
                            $imageName = $imageName . '.png';
                            $content = $image->encode('png');
                            break;
                        }
                        case MimeType::SVG->value:
                        {
                            $imageName = $imageName . '.svg';
                            break;
                        }
                        default:
                        {
                            $segments = explode('.', $fileName);
                            $extension = array_pop($segments);
                            $imageName = $imageName . '.' . $extension;
                        }
                    }

                    $imagePath = "gallery/$this->userId/raw/$imageName";
                    spaces()->put($imagePath, $content);

                    $userImage = UserImage::create([
                        'user_id' => $this->userId,
                        'category_id' => $categoryId,
                        'title' => $fileName,
                        'original_name' => $imageName
                    ]);

                    $userImage->save();

                    $userImage->generateWatermarkImage();

                    printf("Image processed: %s\n", $fileName);
                }
            } catch (\Exception $exception) {
                printf("Error in processing image: %s\n", $fileId);
                printf("Error in processing image: %s\n", $fileName);
                printf("Error in processing image: %s\n", $exception->getMessage());
            }
        }
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $shop = User::find($this->userId);

        if ($shop) {
            if (!$shop->getSetting('importingGoogleDrive', false)) {
                //                $shop->setSetting('importingGoogleDrive', true);

                try {
                    $categories = [];
                    $this->gService = Storage::disk('google')->getAdapter()->getService();

                    $files = $this->gService->files->listFiles([
                        'fields' => 'files(id,name,shared)',
                        'q' => "'{$this->folderId}' in parents and mimeType = 'application/vnd.google-apps.folder'",
                        'supportsAllDrives' => true,
                        'includeItemsFromAllDrives' => true,
                    ])->getFiles();

                    if (!empty($files)) {
                        foreach ($files as $file) {
                            if ($this->sharedOnly && !$file->getShared()) {
                                continue;
                            }

                            $categories[] = [
                                'folderId' => $file->getId(),
                                'name' => $file->getName()
                            ];
                        }
                    }


                    if (count($categories) > 0) {
                        // Create new Categories
                        foreach ($categories as $category) {

                            $imageCategory = UserImageCategory::updateOrCreate([
                                'name' => $category['name'],
                                'user_id' => $this->userId
                            ]);

                            $this->createSubCategories($category['folderId'], $imageCategory->id);

                            try {
                                $this->importImage($category['folderId'], $imageCategory->id);
                            } catch (\Exception $exception) {
                                printf("Error in processing category: %s\n", $category['name']);
                                printf("Error in processing category: %s\n", $exception->getMessage());
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    printf("Error in processing folder: %s\n", $this->folderId);
                    printf("Error in processing folder: %s\n", $exception->getMessage());
                }

                $shop->setSetting('importingGoogleDrive', false);
            }

        } else {
            printf("Shop not found with id: {$this->userId}\n");
        }
    }
}
