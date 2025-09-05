<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'owner_id'      => $this->owner_id,
            'created_at'    => $this->created_at ?? null,
            'my_role'       => $this->when(isset($this->my_role), $this->my_role),
            'members_count' => $this->when(isset($this->members_count), (int) $this->members_count),
        ];
    }
}
