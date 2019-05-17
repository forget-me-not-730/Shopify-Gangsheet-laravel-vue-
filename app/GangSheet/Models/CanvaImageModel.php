<?php

namespace App\GangSheet\Models;

use App\Extensions\Image\Facades\Image;
use App\GangSheet\Exceptions\CanvaExportException;
use App\GangSheet\Facades\Canva;
use Illuminate\Database\Eloquent\Model;

class CanvaImageModel extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_UPLOADING = 1;
    const STATUS_UPLOADED = 2;
    const STATUS_FAILED = 3;

    protected $fillable = [
        'canva_id',
        'user_id',
        'customer_id',
        'session_id',
        'title',
        'width',
        'height',
        'extension',
        'status',
    ];

    protected $appends = [
        'url',
        'thumb_url',
    ];

    protected function getPath(): string
    {
        return "canva_images/{$this->user_id}/{$this->canva_id}_{$this->width}x{$this->height}.{$this->extension}";
    }

    protected function getThumbPath(): string
    {
        return "canva_images/{$this->user_id}/{$this->canva_id}_{$this->width}x{$this->height}_thumb.png";
    }

    function getThumbUrlAttribute(): string
    {
        return spaces()->url($this->getThumbPath());
    }

    function getUrlAttribute(): string
    {
        if (!$this->isUploaded()) {
            return $this->thumb_url;
        }

        return spaces()->url($this->getPath());
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUploading(): bool
    {
        return $this->status === self::STATUS_UPLOADING;
    }

    public function isUploaded(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function upload(int $width = null, int $height = null): array
    {
        if ($this->isUploaded()) {
            return [
                'success' => true,
                'image' => $this,
            ];
        }

        if ($this->isUploading()) {
            return [
                'success' => false,
                'error' => 'Image is already being exported',
            ];
        }

        try {
            $this->update(['status' => self::STATUS_UPLOADING]);

            $token = Canva::getConnectedCustomerToken([
                'session_id' => $this->session_id,
                'customer_id' => $this->customer_id,
            ]);

            if (!$token) {
                throw new CanvaExportException('Access token not found, connect your Canva account again.');
            }

            $designExport = Canva::createDesignExport($this->canva_id, $width, $height, $token->access_token);

            if (!$designExport) {
                throw new CanvaExportException(
                    'Unable to export the design with the specified dimensions. Please try with smaller size or upgrade your account plan and try again.'
                );
            }

            $exportSuccess = $designExport['job']['status'] === 'success';

            while (!$exportSuccess) {
                sleep(2);
                $designExport = Canva::getDesignExport($designExport['job']['id'], $token->access_token);

                if (!$designExport) {
                    throw new CanvaExportException('Unable to get the design export status, please try again.');
                }

                if ($designExport['job']['status'] === 'failed') {
                    throw new CanvaExportException('Design export failed, please try again.');
                }

                $exportSuccess = $designExport['job']['status'] === 'success';
            }

            $exportUrl = $designExport['job']['urls'][0];

            $image = Image::make($exportUrl);
            $image->setResolution();

            $this->fill([
                'status' => self::STATUS_UPLOADED,
                'width' => $image->width(),
                'height' => $image->height(),
            ]);

            spaces()->put($this->getPath(), $image->encode('png'));
            spaces()->put($this->getThumbPath(), $image->encode('png'));

            $this->save();

            return [
                'success' => true,
                'image' => $this,
            ];

        } catch (\Exception $e) {
            report($e);
            $this->forceDelete();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
