<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltersItems
{
    protected function applyItemFilters(Builder $query, Request $request, string $dateColumn): Builder
    {
        return $query
            ->when($request->string('keyword')->isNotEmpty(), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('name', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhere('color', 'like', $keyword)
                        ->orWhere('brand_model', 'like', $keyword)
                        ->orWhere('serial_imei', 'like', $keyword);
                });
            })
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('item_category_id', $request->integer('category_id')))
            ->when($request->filled('campus_id'), fn (Builder $query) => $query->where('campus_id', $request->integer('campus_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate($dateColumn, '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate($dateColumn, '<=', $request->date('to')));
    }
}
