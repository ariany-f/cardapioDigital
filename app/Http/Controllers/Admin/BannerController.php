<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Branch;
use App\Support\BannerImageStorage;
use App\Support\MediaUrl;
use App\Support\SecureImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Banners/Index', [
            'banners' => Banner::query()
                ->with('branch:id,name')
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'branch_id' => $b->branch_id,
                    'branch_name' => $b->branch?->name,
                    'title' => $b->title,
                    'link_url' => $b->link_url,
                    'sort_order' => $b->sort_order,
                    'is_active' => $b->is_active,
                    'image_url' => MediaUrl::fromPath($b->image_path),
                ]),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            ...SecureImageUpload::rules('image', required: true),
        ]);

        $path = BannerImageStorage::store($request->file('image'), \App\Support\TenantContext::id());

        Banner::create([
            'branch_id' => $data['branch_id'],
            'title' => $data['title'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'image_path' => $path,
        ]);

        return back()->with('success', 'Banner criado.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            ...SecureImageUpload::rules('image'),
        ]);

        $updates = [
            'title' => $data['title'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? $banner->sort_order,
            'is_active' => $data['is_active'] ?? false,
        ];

        if ($request->hasFile('image')) {
            BannerImageStorage::delete($banner->image_path);
            $updates['image_path'] = BannerImageStorage::store(
                $request->file('image'),
                $banner->tenant_id,
            );
        }

        $banner->update($updates);

        return back()->with('success', 'Banner atualizado.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        BannerImageStorage::delete($banner->image_path);
        $banner->delete();

        return back()->with('success', 'Banner removido.');
    }
}
