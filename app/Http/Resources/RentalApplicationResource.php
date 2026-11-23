<?php

namespace App\Http\Resources;

use App\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/** @mixin RentalApplication */
class RentalApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     user_id: int,
     *     status: string,
     *     submitted_at: ?string,
     *     reviewed_at: ?string,
     *     reviewed_by: ?int,
     *     created_at: ?string,
     *     updated_at: ?string,
     *     notes: string|null|MissingValue,
     *     customer: array{id: int, first_name: string, last_name: string, email: string}|null|MissingValue
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewed_by' => $this->reviewed_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'notes' => $this->when($request->user()?->isAdmin() === true, $this->notes),
            'customer' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
            ]),
        ];
    }
}
