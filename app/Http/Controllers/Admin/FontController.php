<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Font;
use App\Models\UserFont;
use App\Services\FontService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FontController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 20);
        $status = $request->input('status', 'active');
        $search = $request->input('search', '');

        $query = DB::table('fonts')
            ->leftJoin('user_fonts', function ($join) {
                $join->on('fonts.id', '=', 'user_fonts.font_id')
                    ->whereNull('user_fonts.user_id');
            });

        if ($status != 'all') {
            if ($status === 'name_and_number') {
                $query->where('fonts.type', 'name_and_number');
            } else if ($status == 'active') {
                $query->whereNotNull('user_fonts.font_id');
            } else if ($status == 'inactive') {
                $query->whereNull('user_fonts.font_id');
            }
        }

        if ($search) {
            $query->where('fonts.name', 'like', "%$search%");
        }

        $fonts = $query->select([
            'name',
            DB::raw('GROUP_CONCAT(DISTINCT user_fonts.font_id) as status'),
            DB::raw('GROUP_CONCAT(DISTINCT style) as styles'),
            DB::raw('GROUP_CONCAT(DISTINCT weight) as weights'),
            DB::raw('GROUP_CONCAT(DISTINCT type) as types')
        ])
            ->groupBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Admin/FontsPage', [
            'fonts' => $fonts,
            'defaultFont' => option('default_font'),
            'filters' => [
                'perPage' => $perPage,
                'status' => $status,
                'search' => $search
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file',
            'type' => 'required|in:general,name_and_number'
        ]);

        try {

            foreach ($request->file('files') as $file) {

                $font = \FontLib\Font::load($file);

                $fontPath = 'fonts/' . $font->getFontFullName() . "." . $file->getClientOriginalExtension();

                Storage::disk('spaces')->put($fontPath, file_get_contents($file));
                $fontFullName = $font->getFontFullName();
                $existingFont = Font::where('full_name', $fontFullName)->first();

                if ($existingFont) {
                    return redirect()->back()->withErrors(['error' => 'Font already exists: ' . $fontFullName]);
                }

                Font::updateOrCreate(
                    [
                        'full_name' => $fontFullName,
                        'path' => $fontPath,
                        'type' => $request->input('type'),
                    ],
                    [
                        'name' => $font->getFontName(),
                        'weight' => $font->getFontWeight(),
                        'style' => $font->getFontStyle(),
                    ]
                );
            }

            $fontService = new FontService();

            $fontService->generateAllFontCss();

            if ($request->input('type') === 'name_and_number') {
                $fontService->updateGeneralOptions();
            }

            return redirect()->back()->with('success', 'Font uploaded successfully');

        } catch (\Exception $e) {

            report($e);

            return redirect()->back()->withErrors(['error' => 'An error occurred while uploading the font']);
        }
    }

    public function activate(Request $request)
    {
        $data = $request->validate([
            'font_ids' => 'required|array',
            'font_ids.*' => 'required|exists:fonts,id'
        ]);

        foreach ($data['font_ids'] as $fontId) {
            UserFont::updateOrCreate(
                [
                    'font_id' => $fontId,
                    'user_id' => null
                ],
                [
                    'font_id' => $fontId,
                    'user_id' => null
                ]
            );
        }

        (new FontService())->generateFontCss();

        return redirect()->back()->with('success', 'Font activated successfully');
    }


    public function inactivate(Request $request)
    {
        $data = $request->validate([
            'font_ids' => 'required|array',
            'font_ids.*' => 'required|exists:fonts,id'
        ]);

        foreach ($data['font_ids'] as $fontId) {
            UserFont::where('font_id', $fontId)->whereNull('user_id')->delete();
        }

        (new FontService())->generateFontCss();

        return redirect()->back()->with('success', 'Font inactivated successfully');
    }

    public function default(Request $request)
    {
        option(['default_font' => $request->font]);

        return response()->json([
            'success' => true
        ]);
    }

    public function updateType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:general,name_and_number'
        ]);

        Font::where('name', $data['name'])->update([
            'type' => $data['type']
        ]);

        (new FontService)->updateGeneralOptions();

        return response()->json([
            'success' => true,
            'message' => 'Font type updated successfully'
        ]);
    }
}
