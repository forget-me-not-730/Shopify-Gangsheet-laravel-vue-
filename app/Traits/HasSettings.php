<?php

namespace App\Traits;

trait HasSettings
{
    public function setSetting($key, $value = null): void
    {
        $settings = $this->settings ?? [];

        if (is_array($key)) {
            foreach ($key as $settingKey => $settingValue) {
                if (!is_null($settingValue)) {
                    $settings[$settingKey] = $settingValue;
                }
            }
        } else {
            if (!is_null($value)) {
                $settings[$key] = $value;
            }
        }

        $this->settings = $settings;

        $this->save();
    }

    public function getSetting($key, $default = null)
    {
        $settings = $this->settings ?? [];

        return $settings[$key] ?? $default;
    }

}
