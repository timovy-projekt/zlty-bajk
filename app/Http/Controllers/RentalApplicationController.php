<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexRentalApplicationRequest;
use App\Http\Resources\RentalApplicationResource;
use App\Models\RentalApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RentalApplicationController extends Controller
{
    public function index(IndexRentalApplicationRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('access-customer-dashboard');

        $applications = $request->user()->rentalApplications()
            ->when($request->validated('status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20))
            ->appends($request->safe()->only(['status', 'per_page']));

        return RentalApplicationResource::collection($applications);
    }

    public function show(RentalApplication $rentalApplication): RentalApplicationResource
    {
        Gate::authorize('access-customer-dashboard');

        Gate::authorize('view', $rentalApplication);

        return new RentalApplicationResource($rentalApplication);
    }
}
