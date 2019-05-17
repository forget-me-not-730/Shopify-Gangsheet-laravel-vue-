<?php

namespace App\GangSheet\Services;

use App\GangSheet\Abstracts\OAuthService;
use App\GangSheet\Exceptions\OAuthServiceException;
use App\Models\Design;
use App\Models\InAppAuthToken;
use App\Models\User;
use Google\Service\PeopleService;
use Illuminate\Support\Facades\Http;

class GoogleService extends OAuthService
{
    protected function configure(): void
    {
        $this->clientId = config('services.google.client_id');
        $this->clientSecret = config('services.google.client_secret');
        $this->identifier = 'google';
        $this->redirectUri = route('google-auth.success');
    }

    protected function createClient(): void
    {
        $this->client = new \Google_Client();
        $this->client->setClientId($this->clientId);
        $this->client->setClientSecret($this->clientSecret);
        $this->client->setRedirectUri($this->redirectUri);

        $this->client->setAccessType("offline");
        $this->client->addScope([
            \Google_Service_Drive::DRIVE,
            \Google_Service_Drive::DRIVE_FILE,
            PeopleService::USERINFO_PROFILE,
            PeopleService::USERINFO_EMAIL
        ]);
        $this->client->setPrompt('consent');
    }

    public function getAuthorizeUrl($params): string
    {
        $state = $this->getState($params);

        $this->client->setState($state);
        $this->client->setRedirectUri($this->redirectUri);
        $this->client->setPrompt('consent');
        $this->client->setApprovalPrompt('force');
        $this->client->setAccessType('offline');

        return $this->client->createAuthUrl();
    }

    public function refreshToken(InAppAuthToken $token): ?InAppAuthToken
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://accounts.google.com/o/oauth2/token', [
            'grant_type' => "refresh_token",
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token->refresh_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $token->update([
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
            ]);

            return $token;
        }

        return null;
    }

    function revokeToken(string $accessToken): void
    {
        $this->client->revokeToken($accessToken);
    }

    /**
     * @throws OAuthServiceException
     */
    public function getUserProfile(string $accessToken): array
    {
        try {
            $this->client->setAccessToken($accessToken);
            $oAuth2Service = new \Google_Service_Oauth2($this->client);
            $data = $oAuth2Service->userinfo->get();

            return [
                'email' => $data->email,
                'name' => $data->name,
            ];
        } catch (\Exception $exception) {
            throw new OAuthServiceException('Error getting user profile from Google');
        }
    }

    public function authorizeStoreFromCode(string $code, array $options): array
    {
        try {
            $this->client->setAccessType('offline');
            $this->client->setRedirectUri($this->redirectUri);

            $data = $this->client->fetchAccessTokenWithAuthCode($code);

            if ($data) {

                $this->createOrUpdateStoreToken($data, $options);

                return [
                    'success' => true
                ];
            }

            throw new OAuthServiceException('Error authorizing Dropbox');

        } catch (\Exception $exception) {
            report($exception);

            return [
                'success' => false,
                'response' => $exception->getMessage()
            ];
        }
    }

    public function authorizeCustomerFromCode(string $code, array $options): array
    {
        try {
            $this->client->setAccessType('offline');
            $this->client->setRedirectUri($this->redirectUri);

            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            $this->createOrUpdateCustomerToken($token, $options);

            return [
                'success' => true,
            ];
        } catch (\Exception $exception) {
            throw new OAuthServiceException('Error authorizing customer from Google');
        }
    }

    public function uploadGangSheetImage($user_id, $path, string $imageContent): void
    {
        $token = $this->getConnectedStoreToken($user_id);

        if ($token) {

            try {
                $this->client->refreshToken($token->refresh_token);
                $driveService = new \Google_Service_Drive($this->client);

                $path = str_replace('\\', '/', $path);
                $pathSegments = explode('/', $path);
                $fileName = array_pop($pathSegments);

                $parentId = null;

                // remove empty folder name.
                $pathSegments = array_filter($pathSegments, function ($value) {
                    return !empty($value);
                });

                // create folders if they don't exist.
                foreach ($pathSegments as $folderName) {
                    $folders = $driveService->files->listFiles(array(
                        'q' => "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false" . ($parentId ? " and '$parentId' in parents" : ''),
                    ));

                    if (count($folders) > 0) {
                        $parentId = $folders[0]->id;
                    } else {
                        // Folder doesn't exist, create it
                        $folderMetadata = new \Google_Service_Drive_DriveFile(array(
                            'name' => $folderName,
                            'mimeType' => 'application/vnd.google-apps.folder',
                            'parents' => array($parentId), // Set the parent folder ID
                        ));
                        $folder = $driveService->files->create($folderMetadata, array(
                            'fields' => 'id'
                        ));
                        $parentId = $folder->id;
                    }
                }

                $file = new \Google_Service_Drive_DriveFile(array(
                    'name' => $fileName, // Name of the uploaded file,
                    'parents' => array($parentId)
                ));

                $driveService->files->create($file, [
                    'data' => $imageContent,
                    'mimeType' => 'image/png',
                    'uploadType' => 'media'
                ]);
            } catch (\Exception $exception) {
                report($exception);
            }

        } else {
            slack_report("Google Drive refresh token not found for user $user_id");
        }
    }

    public function fileExists($user_id, $path): bool
    {
        $token = $this->getConnectedStoreToken($user_id);

        if ($token) {
            $this->client->refreshToken($token->refresh_token);
            $driveService = new \Google_Service_Drive($this->client);

            $pathComponents = explode('/', $path);

            // Start with the root folder
            $folderId = null;

            // Iterate over each component of the file path
            foreach ($pathComponents as $folderName) {
                // Search for the current folder by name
                $folderQuery = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false" . ($folderId ? " and '$folderId' in parents" : '');
                $folders = $driveService->files->listFiles(array('q' => $folderQuery))->getFiles();

                // Check if the folder exists
                if (count($folders) == 0) {
                    break;
                }

                // Get the ID of the first folder (assuming unique folder names)
                $folderId = $folders[0]->getId();
            }

            if (!empty($folderId)) {
                // At this point, $folderId contains the ID of the folder containing the file
                // Search for the file within this folder
                $fileName = end($pathComponents); // Get the file name from the end of the path
                $fileQuery = "mimeType!='application/vnd.google-apps.folder' and name='$fileName' and trashed=false and '$folderId' in parents";
                $files = $driveService->files->listFiles(array('q' => $fileQuery))->getFiles();

                // Return true if the file exists, false otherwise
                return count($files) > 0;
            }
        }

        return false;
    }

    public function getGoogleDriveFiles($user_id): void
    {
        $gangSheets = [];

        $token = $this->getConnectedStoreToken($user_id);
        $user = User::find($user_id);
        $googleDriveFolderName = $user->getSetting('googleDriveFolderName');
        $this->client->refreshToken($token->refresh_token);
        $driveService = new \Google_Service_Drive($this->client);

        $rootFolder = explode('/', $googleDriveFolderName)[0];
        $rootFolderId = null;

        $files = $driveService->files->listFiles([
            'fields' => 'files(id,name)',
            'q' => "mimeType = 'application/vnd.google-apps.folder' and trashed=false and name = '$rootFolder'"
        ])->getFiles();

        if (count($files) > 0) {
            $rootFolderId = $files[0]->id;
        }

        if ($rootFolderId) {
            $files = $driveService->files->listFiles([
                'fields' => 'files(id,name)',
                'q' => "'$rootFolderId' in parents and trashed=false"
            ])->getFiles();

            foreach ($files as $file) {
                $gangSheets[] = $file->name;
            }
        }

        dd($gangSheets);
    }

    public function syncGoogleDriveFiles($user_id): void
    {
        $designs = Design::whereNotNull('order_id')
            ->where('user_id', $user_id)
            ->where('status', Design::STATUS_COMPLETED)
            ->get();

        foreach ($designs as $design) {

            $googleDrivePath = $design->getGoogleDrivePath();

            if ($this->fileExists($user_id, $googleDrivePath)) {
                continue;
            }

            if ($content = $design->getGangSheetFileContent()) {
                $this->uploadGangSheetImage($user_id, $googleDrivePath, $content);

                echo "Uploaded to Google Drive: $googleDrivePath\n";
            }
        }
    }
}
