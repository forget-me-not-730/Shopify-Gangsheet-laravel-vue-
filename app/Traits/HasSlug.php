<?php

namespace App\Traits;

use FontLib\Table\Type\name;
use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $model->configSlug();
        });
    }

    public function configSlug(): void
    {
        $slug = $this->{$this->getSlugFieldName()};
        if (empty($slug)) {
            $slug = Str::slug($this->{$this->getSlugFromField()});
        }
        $this->{$this->getSlugFieldName()} = $this->generateUniqueSlug($slug);
    }

    public function getSlugFromField(): string
    {
        return $this->slugFromField ?? 'name';
    }

    public function getSlugFieldName(): string
    {
        return $this->slugFieldName ?? 'slug';
    }

    public function getSlugUniqueWith(): array
    {
        return $this->slugUniqueWith ?? [];
    }

    public function generateUniqueSlug(string $slug): string
    {
        // Check if the modified slug already exists in the table
        $existingSlugs = $this->getExistingSlugs($slug);

        if (!in_array($slug, $existingSlugs)) {
            return $slug;
        }

        $slugNumber = 0;

        if (preg_match('/-(\d+)$/', $slug, $matches)) {
            $slugNumber = $matches[1];
            $slug = Str::replaceLast("-$slugNumber", '', $slug);
        }

        // Increment the number until a unique slug is found
        $i = $slugNumber + 1;

        while (1) {
            $newSlug = $slug . '-' . $i;
            if (!in_array($newSlug, $existingSlugs)) {
                return $newSlug;
            }
            $i++;
        }
    }

    private function getExistingSlugs(string $slug): array
    {
        $query = $this->where($this->getSlugFieldName(), 'LIKE', $slug . '%')
            ->where('id', '!=', $this->id ?? null);

        $slugUniqueWith = $this->getSlugUniqueWith();

        foreach ($slugUniqueWith as $field) {
            $query->where($field, $this->{$field});
        }

        if (method_exists($this, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->pluck($this->getSlugFieldName())
            ->toArray();
    }
}
