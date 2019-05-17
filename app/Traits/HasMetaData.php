<?php

namespace App\Traits;

use App\Models\MetaData;

trait HasMetaData
{
    public function metaData()
    {
        return $this->morphMany(MetaData::class, 'model');
    }

    public function getMetaData($key, $default = null)
    {
        $metaData = $this->metaData()->where('key', $key)->first();

        return $metaData ? $metaData->value : $default;
    }

    public function setMetaData($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setMetaData($k, $v);
            }
        } else {
            if (!empty($value)) {
                $this->metaData()->updateOrCreate(['key' => $key], ['value' => $value]);
            } else {
                $this->metaData()->where('key', $key)->delete();
            }
        }
    }

    public function removeMetaData($key): void
    {
        $this->metaData()->where('key', $key)->delete();
    }
}
