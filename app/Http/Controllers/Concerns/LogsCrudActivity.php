<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

trait LogsCrudActivity
{
    protected function logCrud(Model $subject, string $action, string $description, array $properties = []): void
    {
        app(ActivityLogService::class)->log($subject, $action, $description, $properties, 'admin');
    }
}
