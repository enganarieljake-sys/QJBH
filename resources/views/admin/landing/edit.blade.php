@extends('layouts.app')

@section('title', 'Landing Page')
@section('page-title', 'Landing Page')
@section('page-subtitle', 'Change what visitors see on the public home page.')
@section('page-actions')
    <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">
        View Landing Page
        {{ svg('solar-outline-square-arrow-right-up', 'size-4', ['aria-hidden' => 'true']) }}
    </a>
@endsection

@section('content')
    <div class="max-w-5xl space-y-6">
        <form method="POST" action="{{ route('admin.landing.update') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            @method('PUT')
            <h2 class="font-semibold text-slate-950">Text &amp; sections</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label for="landing_headline" class="block text-sm font-medium text-slate-700">Headline</label>
                    <input id="landing_headline" name="landing_headline" required maxlength="120" value="{{ old('landing_headline', $headline) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('landing_headline') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="landing_intro" class="block text-sm font-medium text-slate-700">Welcome message</label>
                    <textarea id="landing_intro" name="landing_intro" rows="4" required maxlength="1000" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('landing_intro', $intro) }}</textarea>
                    @error('landing_intro') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-slate-700">
                    <label class="flex items-center gap-2"><input type="checkbox" name="landing_show_gallery" value="1" @checked($showGallery) class="rounded border-slate-300"> Show photo gallery</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="landing_show_rooms" value="1" @checked($showRooms) class="rounded border-slate-300"> Show available rooms</label>
                </div>
            </div>
            <button class="mt-6 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Text &amp; Sections</button>
        </form>

        <form method="POST" action="{{ route('admin.landing.photos.store') }}" enctype="multipart/form-data" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            <h2 class="font-semibold text-slate-950">Add photos</h2>
            <p class="mt-1 text-sm text-slate-600">Show off the house: the building, rooms, kitchen, study area. Up to 10 at a time, 4 MB each.</p>
            <input type="file" name="photos[]" accept="image/png,image/jpeg,image/webp" multiple required aria-label="Photos to upload" class="mt-4 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('photos') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            @error('photos.*') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            <button class="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Upload Photos</button>
        </form>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200" aria-labelledby="gallery-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="gallery-heading" class="font-semibold text-slate-950">Gallery ({{ $photos->count() }})</h2>
                @if ($backgroundId)
                    <form method="POST" action="{{ route('admin.landing.background') }}">
                        @csrf
                        @method('PUT')
                        <button class="rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700">Use default background</button>
                    </form>
                @endif
            </div>

            @if ($photos->isEmpty())
                <p class="mt-4 text-sm text-slate-500">No photos yet. Upload some above.</p>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($photos as $photo)
                        <article class="landing-photo-card overflow-hidden rounded-lg ring-1 ring-slate-200 {{ $photo->is_visible ? '' : 'is-hidden' }}">
                            <div class="relative">
                                <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?: 'Gallery photo' }}" loading="lazy" class="aspect-video w-full object-cover">
                                <div class="absolute left-2 top-2 flex gap-1">
                                    @if ($backgroundId === $photo->id)<span class="landing-photo-tag">Background</span>@endif
                                    @unless ($photo->is_visible)<span class="landing-photo-tag landing-photo-tag--muted">Hidden</span>@endunless
                                </div>
                            </div>
                            <div class="space-y-3 p-3">
                                <form method="POST" action="{{ route('admin.landing.photos.update', $photo) }}" class="space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <input name="caption" maxlength="120" value="{{ $photo->caption }}" placeholder="Caption (optional)" aria-label="Caption" class="w-full rounded-md border border-slate-300 px-2 py-1 text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <label class="flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="is_visible" value="1" @checked($photo->is_visible) class="rounded border-slate-300"> Show on site</label>
                                        <button class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Save</button>
                                    </div>
                                </form>
                                <div class="flex flex-wrap gap-1">
                                    @foreach (['up' => 'â†‘ Earlier', 'down' => 'â†“ Later'] as $direction => $label)
                                        <form method="POST" action="{{ route('admin.landing.photos.move', $photo) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direction" value="{{ $direction }}">
                                            <button class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700" @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last))>{{ $label }}</button>
                                        </form>
                                    @endforeach
                                    @if ($backgroundId !== $photo->id)
                                        <form method="POST" action="{{ route('admin.landing.background') }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="photo_id" value="{{ $photo->id }}">
                                            <button class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Set as background</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.landing.photos.destroy', $photo) }}" onsubmit="return confirm('Remove this photo from the website?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <p class="text-sm text-slate-600">Room photos in the "Available rooms" section come from <a href="{{ route('admin.rooms.index') }}" class="font-semibold underline">Room Management</a>. Edit a room to change its photo.</p>
    </div>
@endsection
