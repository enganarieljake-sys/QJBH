<?php

namespace App\Http\Controllers;

use App\Models\LandingPhoto;
use App\Models\Room;
use App\Models\Setting;
use App\Support\PaymentFiles;
use Throwable;

/**
 * Public pages: the landing page lists vacant rooms, and room photos are
 * served without login so applicants can see them.
 */
class PublicRoomController extends Controller
{
    public function landing()
    {
        $page = [
            'headline' => Setting::DEFAULTS['landing_headline'],
            'intro' => Setting::DEFAULTS['landing_intro'],
            'showGallery' => false,
            'showRooms' => true,
            'gallery' => collect(),
            'vacantRooms' => collect(),
        ];

        // Keep the landing page up even if the database is unreachable.
        try {
            $page['headline'] = Setting::get('landing_headline');
            $page['intro'] = Setting::get('landing_intro');
            $page['showGallery'] = Setting::get('landing_show_gallery') === '1';
            $page['showRooms'] = Setting::get('landing_show_rooms') === '1';
            $page['gallery'] = $page['showGallery'] ? LandingPhoto::ordered()->where('is_visible', true)->get() : collect();
            $page['vacantRooms'] = $page['showRooms'] ? Room::vacant() : collect();
        } catch (Throwable $e) {
            report($e);
        }

        return view('landing', $page);
    }

    public function landingPhoto(LandingPhoto $landingPhoto)
    {
        return PaymentFiles::response($landingPhoto->disk, $landingPhoto->path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function houseRules()
    {
        return view('house-rules', ['rules' => Setting::get('house_rules')]);
    }

    public function photo(Room $room)
    {
        abort_unless($room->photo, 404);

        return PaymentFiles::response($room->photo_disk, $room->photo, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
