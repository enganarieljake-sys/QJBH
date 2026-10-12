<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'values' => collect(array_keys(Setting::DEFAULTS))->mapWithKeys(fn ($key) => [$key => Setting::get($key)]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'reminder_days_before_due' => ['required', 'integer', 'min:0', 'max:30'],
            'receipt_keep_months' => ['required', 'integer', 'min:0', 'max:60'],
            'house_rules' => ['required', 'string', 'max:10000'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, (string) $value);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }
}
