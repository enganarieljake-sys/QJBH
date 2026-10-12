<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPhoto;
use App\Models\Setting;
use App\Support\PaymentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lets Ate Jackie change what the public landing page shows: the text,
 * a photo gallery, the background photo, and which sections appear.
 */
class LandingPageController extends Controller
{
    public function edit()
    {
        return view('admin.landing.edit', [
            'photos' => LandingPhoto::ordered()->get(),
            'headline' => Setting::get('landing_headline'),
            'intro' => Setting::get('landing_intro'),
            'showGallery' => Setting::get('landing_show_gallery') === '1',
            'showRooms' => Setting::get('landing_show_rooms') === '1',
            'backgroundId' => (int) Setting::get('landing_background_photo_id'),
        ]);
    }

    public function updateContent(Request $request)
    {
        $data = $request->validate([
            'landing_headline' => ['required', 'string', 'max:120'],
            'landing_intro' => ['required', 'string', 'max:1000'],
        ]);

        Setting::put('landing_headline', $data['landing_headline']);
        Setting::put('landing_intro', $data['landing_intro']);
        Setting::put('landing_show_gallery', $request->boolean('landing_show_gallery') ? '1' : '0');
        Setting::put('landing_show_rooms', $request->boolean('landing_show_rooms') ? '1' : '0');

        return back()->with('success', 'Landing page text and sections saved.');
    }

    public function storePhotos(Request $request)
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'photos.max' => 'Upload up to 10 photos at a time.',
            'photos.*.max' => 'Each photo must be 4 MB or smaller.',
        ]);

        $nextOrder = (int) LandingPhoto::max('sort_order');

        foreach ($request->file('photos') as $file) {
            $stored = PaymentFiles::store($file, 'landing');
            LandingPhoto::create([
                'path' => $stored['path'],
                'disk' => $stored['disk'],
                'sort_order' => ++$nextOrder,
            ]);
        }

        return back()->with('success', count($request->file('photos')).' photo(s) added to the gallery.');
    }

    public function updatePhoto(Request $request, LandingPhoto $landingPhoto)
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        $landingPhoto->update([
            'caption' => $data['caption'] ?? null,
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return back()->with('success', 'Photo updated.');
    }

    public function movePhoto(Request $request, LandingPhoto $landingPhoto)
    {
        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        DB::transaction(function () use ($landingPhoto, $direction) {
            // Normalise first so swapping always works, even after deletes.
            LandingPhoto::ordered()->get()->each(fn ($photo, $index) => $photo->update(['sort_order' => $index + 1]));
            $landingPhoto->refresh();

            $neighbour = LandingPhoto::where('sort_order', $direction === 'up' ? '<' : '>', $landingPhoto->sort_order)
                ->orderBy('sort_order', $direction === 'up' ? 'desc' : 'asc')
                ->first();

            if ($neighbour) {
                [$a, $b] = [$landingPhoto->sort_order, $neighbour->sort_order];
                $landingPhoto->update(['sort_order' => $b]);
                $neighbour->update(['sort_order' => $a]);
            }
        });

        return back();
    }

    public function setBackground(Request $request)
    {
        $data = $request->validate([
            'photo_id' => ['nullable', 'exists:landing_photos,id'],
        ]);

        Setting::put('landing_background_photo_id', isset($data['photo_id']) ? (string) $data['photo_id'] : null);

        return back()->with('success', isset($data['photo_id']) ? 'Background photo updated.' : 'Background reset to the default photo.');
    }

    public function destroyPhoto(LandingPhoto $landingPhoto)
    {
        if ((int) Setting::get('landing_background_photo_id') === $landingPhoto->id) {
            Setting::put('landing_background_photo_id', null);
        }

        PaymentFiles::delete($landingPhoto->disk, $landingPhoto->path);
        $landingPhoto->delete();

        return back()->with('success', 'Photo removed.');
    }
}
