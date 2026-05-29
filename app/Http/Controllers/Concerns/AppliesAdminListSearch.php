<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesAdminListSearch
{
    protected function listSearchTerm(Request $request): ?string
    {
        $q = trim((string) $request->input('q', ''));

        return $q === '' ? null : $q;
    }

    /** @return array{q?: string} */
    protected function listSearchFilters(Request $request): array
    {
        $term = $this->listSearchTerm($request);

        return $term === null ? [] : ['q' => $term];
    }

    /**
     * @param  array<string|callable(Builder, string, string): void>  $columns
     */
    protected function applyListSearch(Builder $query, ?string $term, array $columns): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $inner) use ($like, $columns, $term) {
            foreach ($columns as $column) {
                if (is_callable($column)) {
                    $column($inner, $term, $like);

                    continue;
                }

                $inner->orWhere($column, 'like', $like);
            }
        });
    }
}
