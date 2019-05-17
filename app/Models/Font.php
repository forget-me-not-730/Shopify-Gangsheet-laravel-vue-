<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Font extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'full_name',
        'style',
        'weight',
        'format',
        'path',
        'type',
    ];

    protected $appends = ['file_url', 'active'];

    public function userFonts()
    {
        return $this->hasMany(UserFont::class);
    }

    public function getFileUrlAttribute(): string
    {
        return str_replace('\\', '/', Storage::disk('spaces')->url($this->path));
    }

    public function getActiveAttribute(): bool
    {
        return UserFont::where('font_id', $this->id)->whereNull('user_id')->exists();
    }
}
