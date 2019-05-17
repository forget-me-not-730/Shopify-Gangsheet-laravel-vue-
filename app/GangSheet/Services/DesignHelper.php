<?php

namespace App\GangSheet\Services;

class DesignHelper
{
    static function getHistories($designId): array
    {
        if (spaces()->exists("designs/{$designId}")) {
            $historyFiles = spaces()->files("designs/{$designId}");

            $dates = array_map(function ($file) {
                return str_replace(".json", '', basename($file));
            }, $historyFiles);

            usort($dates, function ($a, $b) {
                return strtotime($b) - strtotime($a);
            });

            return $dates;
        } else {
            return [];
        }
    }

    static function getEditUrl($design_id, $token = null): string
    {
        $params = [
            'design_id' => $design_id
        ];

        if ($token) {
            $params['token'] = $token;
        }

        return route('builder.edit', $params);
    }

    static function getCustomerEditUrl($design): string
    {
        return self::getEditUrl($design->id);
    }

    static function getAdminEditUrl($design): string
    {
        return self::getEditUrl($design->id, $design->access_token);
    }
}
