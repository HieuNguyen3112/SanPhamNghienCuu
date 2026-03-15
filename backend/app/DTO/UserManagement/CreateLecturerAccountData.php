<?php

namespace App\DTO\UserManagement;

class CreateLecturerAccountData
{
    public function __construct(
        public string $lecturerCode,
        public string $fullName,
        public string $email,
        public ?string $phoneNumber,
        public ?string $academicTitle,
        public ?int $degreeId,
        public ?int $academicRankId,
        public bool $isActive,
        public int $departmentId,
        public int $facultyId,
        public int $creatorUserId,
    ) {}

    public static function fromFacultyRequest(array $payload, int $creatorUserId, int $departmentId, int $facultyId): self
    {
        return new self(
            lecturerCode: strtoupper((string) $payload['lecturer_code']),
            fullName: (string) $payload['full_name'],
            email: strtolower((string) $payload['email']),
            phoneNumber: $payload['phone_number'] ?? null,
            academicTitle: $payload['academic_title'] ?? null,
            degreeId: isset($payload['degree_id']) ? (int) $payload['degree_id'] : null,
            academicRankId: isset($payload['academic_rank_id']) ? (int) $payload['academic_rank_id'] : null,
            isActive: (($payload['status'] ?? 'ACTIVE') === 'ACTIVE'),
            departmentId: $departmentId,
            facultyId: $facultyId,
            creatorUserId: $creatorUserId,
        );
    }
}
