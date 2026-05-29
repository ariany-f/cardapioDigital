<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Support\BranchCoverStorage;
use App\Support\SecureImageUpload;
use Illuminate\Http\Request;

trait HandlesBranchCoverUpload
{
    protected function coverImageRules(): array
    {
        return SecureImageUpload::rules('cover_image');
    }

    protected function storeBranchCover(Request $request, Branch $branch, int $tenantId): void
    {
        if (! $request->hasFile('cover_image')) {
            return;
        }

        BranchCoverStorage::delete($branch->cover_image_path);

        $branch->update([
            'cover_image_path' => BranchCoverStorage::store(
                $request->file('cover_image'),
                $tenantId,
                $branch->slug,
            ),
        ]);
    }
}
