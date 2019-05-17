<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Font;
use App\Services\FontService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FontController extends Controller
{
    public function index(Request $request)
    {

        $shop = $request->user();

        $perPage = $request->input('perPage', 10);
        $search = $request->input('search', '');

        $query = Font::query();

        $query->whereHas('userFonts', function ($query) use ($shop) {
            $query->where('user_id', $shop->id);
        });

        if ($search) {
            $query->where('full_name', 'like', "%$search%");
        }

        $fonts = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        $adminFonts = DB::table('fonts')->select('name')
            ->leftJoin('user_fonts', 'fonts.id', '=', 'user_fonts.font_id')
            ->where(function (Builder $query) use ($request) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '!=', $request->user()->id);
            })
            ->distinct('name')
            ->orderBy('name')
            ->get();

        return inertia('Merchant/FontsPage', [
            'fonts' => $fonts,
            'adminFonts' => $adminFonts,
            'filters' => [
                'perPage' => $perPage,
                'search' => $search
            ]
        ]);
    }

    public function store(Request $request)
    {
        try {

            $uploadedFonts = [];

            if (!empty($request->file('files'))) {

                foreach ($request->file('files') as $file) {

                    $font = \FontLib\Font::load($file);

                    $fontPath = 'fonts/' . $font->getFontFullName() . "." . $file->getClientOriginalExtension();

                    Storage::disk('spaces')->put($fontPath, file_get_contents($file));

                    $newFont = Font::updateOrCreate(
                        [
                            'full_name' => $font->getFontFullName(),
                            'path' => $fontPath
                        ],
                        [
                            'name' => $font->getFontName(),
                            'weight' => $font->getFontWeight(),
                            'style' => $font->getFontStyle(),
                        ]
                    );

                    $uploadedFonts[] = $newFont->id;
                }

                (new FontService())->generateAllFontCss();
            }

            if (!empty($request->fonts)) {
                $fonts = Font::whereIn('name', $request->fonts)->get();

                foreach ($fonts as $font) {
                    $uploadedFonts[] = $font->id;
                }
            }

            foreach ($uploadedFonts as $fontId) {
                DB::table('user_fonts')->updateOrInsert([
                    'user_id' => $request->user()->id,
                    'font_id' => $fontId
                ]);
            }


            (new FontService())->generateFontCss($request->user()->id);

            return redirect()->back()->with('success', 'Font uploaded successfully');

        } catch (\Exception $e) {

            report($e);

            return redirect()->back()->withErrors(['error' => 'An error occurred while uploading the font']);
        }
    }

    public function delete(Request $request)
    {
        $ids = $request->ids;

        DB::table('user_fonts')->where('user_id', $request->user()->id)
            ->whereIn('font_id', $ids)->delete();

        (new FontService())->generateFontCss($request->user()->id);

        return response()->json([
            'success' => true
        ]);
    }

    public function default(Request $request)
    {
        $request->user()->setSetting('defaultFont', $request->font);

        return response()->json([
            'success' => true
        ]);
    }
}
