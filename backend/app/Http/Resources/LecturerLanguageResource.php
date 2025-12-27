<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LecturerLanguageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'language' => $this->language,
            'level' => $this->proficiency_level,
            'is_native' => $this->is_native,
            'certificate_name' => $this->certificate_name,
            'certificate_level' => $this->certificate_level,
            'certificate_score' => $this->certificate_score,
            'certificate_issuer' => $this->issued_by,
            'issue_date' => $this->issued_at?->toDateString(),
            'expire_date' => $this->expires_at?->toDateString(),
            'note' => $this->notes,
            'attachment_name' => null,
        ];
    }
}
