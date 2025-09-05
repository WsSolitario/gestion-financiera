<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
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
            'inviter_id'    => $this->inviter_id,
            'invitee_email' => $this->invitee_email,
            'group_id'      => $this->group_id,
            'token'         => $this->token,
            'status'        => $this->status,
            'expires_at'    => $this->expires_at,
            'created_at'    => $this->created_at ?? null,
            'updated_at'    => $this->updated_at ?? null,
        ];
    }
}
