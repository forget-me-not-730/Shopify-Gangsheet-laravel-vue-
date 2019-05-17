<?php

namespace App\Http\Controllers\Merchant;

use App\GangSheet\Facades\Dropbox;
use App\Http\Controllers\Controller;
use App\Jobs\RegenerateWatermarkImages;
use App\Models\UserImage;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    function index()
    {
        return inertia('Merchant/Settings');
    }


    function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
        ]);

        $user->update([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        return redirect()->back();
    }

    function getCredit(Request $request)
    {
        $user = $request->user();

        $auto_charge_amount = $user->getMetaData('auto_charge_amount', 20);
        $auto_min_credits = $user->getMetaData('auto_min_credits', 2);
        $auto_charge_enabled = $user->getMetaData('auto_charge_enabled', false);

        return response()->json([
            'success' => true,
            'auto_charge_amount' => $auto_charge_amount,
            'auto_min_credits' => $auto_min_credits,
            'auto_charge_enabled' => $auto_charge_enabled
        ]);
    }

    function updateCredit(Request $request)
    {
        $user = $request->user();
        $data = $this->validate($request, [
            'auto_charge_amount' => 'required',
            'auto_min_credits' => 'required',
            'auto_charge_enabled' => 'required|boolean'
        ]);

        $user->setMetaData([
            'auto_charge_amount' => $data['auto_charge_amount'],
            'auto_min_credits' => $data['auto_min_credits'],
            'auto_charge_enabled' => $data['auto_charge_enabled']
        ]);

        return redirect()->back();
    }

    function updateCompany(Request $request)
    {
        $user = $request->user();

        $data = $this->validate($request, [
            'company_name' => 'required|string',
            'slug' => 'required|string|regex:/^[a-z1-9\-]+$/|min:4|unique:users,slug,' . $user->id,
            'website' => 'nullable|string',
            'logo' => 'nullable|string'
        ]);

        $user->update([
            'company_name' => $data['company_name'],
            'slug' => $data['slug'],
            'website' => $data['website']
        ]);

        if (isset($data['logo'])) {
            $user->updateLogo($data['logo']);
        }

        return redirect()->back();
    }

    function updateBuilder(Request $request)
    {
        $user = $request->user();

        $data = $this->validate($request, [
            'galleryMode' => 'required|string',
            'show_gallery' => 'required|boolean',
            'watermark_opacity' => 'required|numeric',
            'disableBackgroundRemoveTool' => 'nullable|boolean',
            'disableTextFeature' => 'nullable|boolean',
            'allowedAutoBuild' => 'nullable|boolean',
            'agree_check_flag' => 'nullable|boolean',
            'file_types' => 'required|array',
            'agree_label' => 'required|string',
            'gangSheetFileName' => 'nullable|string',
            'startModalView' => 'nullable|string',
            'startModals' => 'required|array',
            'startModals.*.id' => 'nullable|numeric',
            'startModals.*.label' => 'nullable|string',
            'startModals.*.image' => 'nullable|string',
            'startModals.*.enabled' => 'nullable|boolean',
            'language' => 'nullable|string',
            'gangSheetFileExtension' => 'nullable|string',
            'turnOnMargin' => 'nullable|boolean',
            'defaultMarginSize' => 'nullable|numeric|min:0',
            'defaultMarginUnit' => 'nullable|string',
            'turnOnArtboardMargin' => 'nullable|boolean',
            'defaultArtboardMarginSize' => 'nullable|numeric|min:0',
            'defaultArtboardMarginUnit' => 'nullable|string',
            'printFileName' => 'nullable|boolean',
            'printFileNamePosition' => 'nullable|string',
            'useCustomTextColors' => 'nullable|boolean',
            'customTextColors' => 'nullable|array',
            'useCustomImageOverlayColors' => 'nullable|boolean',
            'customImageOverlayColors' => 'nullable|array',
            'autoTrimGangSheet' => 'nullable|boolean',
            'collectShippingAddress' => 'nullable|boolean',
            'printFileNameHeight' => 'nullable|numeric',
            'useUploadDropbox' => 'nullable|boolean',
            'dropboxFolderName' => 'nullable|string',
            'enableChat' => 'nullable|boolean',
            'chatScript' => 'nullable|string',
            'showStartModal' => 'nullable|boolean',
            'shop_email' => 'nullable|string',
            'enableCanva' => 'nullable|boolean',
            'enableImageBackgroundWarning' => 'nullable|boolean',
            'ensureOptimalResolution' => 'nullable|boolean',
            'enableAddNewDesign' => 'nullable|boolean',
            'enableQuantity' => 'nullable|boolean',
            'confirmationButtonLabel' => 'nullable|string',
            'enableFlipping' => 'nullable|boolean',

            // theme settings
            'builderFont' => 'nullable|string',
            'builderBgColor' => 'nullable|string',
            'builderTopBgColor' => 'nullable|string',
            'builderSideBgColor' => 'nullable|string',
            'builderPrimaryColor' => 'nullable|string',
            'builderSecondaryColor' => 'nullable|string',
            'builderFgColor' => 'nullable|string',
            'enableColorOverlay' => 'nullable|boolean',
            'colorOverlayAllowed' => 'nullable|string',
        ]);

        $user->update([
            'show_gallery' => $data['show_gallery'],
        ]);

        $user->setSetting([
            'galleryMode' => $data['galleryMode'] ?? 'dropdown',
            'watermark_opacity' => $data['watermark_opacity'] ?? 1,
            'disableBackgroundRemoveTool' => $data['disableBackgroundRemoveTool'] ?? false,
            'disableTextFeature' => $data['disableTextFeature'] ?? false,
            'allowedAutoBuild' => $data['allowedAutoBuild'] ?? true,
            'agree_check_flag' => $data['agree_check_flag'] ?? false,
            'gangSheetFileName' => $data['gangSheetFileName'] ?? config('custom.default_file_name'),
            'agree_label' => $data['agree_label'] ?? config('custom.default_agree_label'),
            'file_types' => $data['file_types'] ?? [],
            'startModalView' => $data['startModalView'] ?? null,
            'language' => $data['language'] ?? null,
            'startModals' => $data['startModals'] ?? [],
            'gangSheetFileExtension' => $data['gangSheetFileExtension'] ?? null,
            'turnOnMargin' => $data['turnOnMargin'] ?? null,
            'defaultMarginSize' => $data['defaultMarginSize'] ?? 0.5,
            'defaultMarginUnit' => $data['defaultMarginUnit'] ?? 'in',
            'turnOnArtboardMargin' => $data['turnOnArtboardMargin'] ?? null,
            'defaultArtboardMarginSize' => $data['defaultArtboardMarginSize'] ?? 0.5,
            'defaultArtboardMarginUnit' => $data['defaultArtboardMarginUnit'] ?? 'in',
            'printFileName' => $data['printFileName'] ?? null,
            'printFileNamePosition' => $data['printFileNamePosition'] ?? null,
            'useCustomTextColors' => $data['useCustomTextColors'] ?? false,
            'customTextColors' => $data['customTextColors'] ?? [],
            'useCustomImageOverlayColors' => $data['useCustomImageOverlayColors'] ?? false,
            'customImageOverlayColors' => $data['customImageOverlayColors'] ?? [],
            'enableAddNewDesign' => $data['enableAddNewDesign'] ?? true,
            'enableQuantity' => $data['enableQuantity'] ?? true,
            'confirmationButtonLabel' => $data['confirmationButtonLabel'] ?? config('custom.default_confirmation_label'),
            'autoTrimGangSheet' => $data['autoTrimGangSheet'] ?? false,
            'collectShippingAddress' => $data['collectShippingAddress'] ?? true,
            'printFileNameHeight' => $data['printFileNameHeight'] ?? null,
            'useUploadDropbox' => $data['useUploadDropbox'] ?? false,
            'dropboxFolderName' => $data['dropboxFolderName'] ?? null,
            'enableChat' => $data['enableChat'] ?? false,
            'chatScript' => $data['chatScript'] ?? null,
            'showStartModal' => $data['showStartModal'] ?? true,
            'shop_email' => $data['shop_email'] ?? null,
            'enableCanva' => $data['enableCanva'] ?? null,
            'enableImageBackgroundWarning' => $data['enableImageBackgroundWarning'] ?? false,
            'ensureOptimalResolution' => $data['ensureOptimalResolution'] ?? true,
            'enableFlipping' => $data['enableFlipping'] ?? true,

            // theme settings
            'builderFont' => $data['builderFont'] ?? null,
            'builderBgColor' => $data['builderBgColor'] ?? null,
            'builderTopBgColor' => $data['builderTopBgColor'] ?? null,
            'builderSideBgColor' => $data['builderSideBgColor'] ?? null,
            'builderPrimaryColor' => $data['builderPrimaryColor'] ?? null,
            'builderSecondaryColor' => $data['builderSecondaryColor'] ?? null,
            'builderFgColor' => $data['builderFgColor'] ?? null,
            'enableColorOverlay' => $data['enableColorOverlay'] ?? false,
            'colorOverlayAllowed' => $data['colorOverlayAllowed'] ?? null,
        ]);

        return redirect()->back();
    }

    function updatePassword(Request $request)
    {
        $admin_id = $request->session()->get('admin_id');
        $user = $request->user();

        if ($admin_id) {
            $data = $this->validate($request, [
                'password' => 'required|confirmed'
            ]);
            $user->update([
                'password' => bcrypt($data['password'])
            ]);
        } else {
            $data = $this->validate($request, [
                'current_password' => 'required|string',
                'password' => 'required|confirmed'
            ]);
            $user->update([
                'password' => bcrypt($data['password'])
            ]);

            if (auth()->validate(['email' => $user->email, 'password' => $data['current_password']])) {
                $user->update([
                    'password' => bcrypt($data['password'])
                ]);
            } else {
                return redirect()->back()->withErrors(['current_password' => 'Password is incorrect.']);
            }

        }

        return redirect()->back()->with('success', 'Password updated successfully');
    }

    function support()
    {
        return inertia('Merchant/Support');
    }

    public function checkWatermarkOpacityStatus(Request $request)
    {
        $user = $request->user();
        $total = UserImage::where('user_id', $user->id)->count();

        return response()->json([
            'running' => $user->getSetting('watermark_processing', false),
            'processed' => $user->getSetting('processed', 0),
            'total' => $total
        ]);
    }

    public function applyWatermarkOpacity(Request $request)
    {
        $data = $request->validate([
            'watermark_opacity' => 'required|numeric',
        ]);

        $user = $request->user();

        if ($user->getSetting('watermark_processing', false)) {
            return;
        }

        $user->setSetting('watermark_opacity', $data['watermark_opacity'] ?? 0.5);

        $user->setWatermarkProcessing(true);

        RegenerateWatermarkImages::dispatch($user->id);
    }

    public function dropbox(Request $request)
    {
        $user = $request->user();

        $dropboxAuthUrl = Dropbox::getAuthorizeUrl([
            'user_id' => $user->id,
        ]);

        $dropboxToken = Dropbox::getConnectedStoreToken($user->id);

        $dropboxFolderName = $user->getSetting('dropboxFolderName', 'gang_sheets');

        return response()->json([
            'success' => true,
            'auth_url' => $dropboxAuthUrl,
            'connected' => !empty($dropboxToken),
            'token' => $dropboxToken ?? null,
            'folderName' => $dropboxFolderName
        ]);
    }

    public function revokeDropbox(Request $request)
    {
        $user = $request->user();

        Dropbox::revokeStoreAccess($user->id);

        return response()->json([
            'success' => true
        ]);
    }
}
