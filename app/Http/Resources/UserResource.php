<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'profile_picture_url'=> $this->profile_picture_url,
            'phone_number'       => $this->phone_number,
            'created_at'         => $this->created_at ?? null,
            'updated_at'         => $this->updated_at ?? null,
        ];
    }
}
