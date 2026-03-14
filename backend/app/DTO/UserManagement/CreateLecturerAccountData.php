<?php

namespace App\DTO\UserManagement;

class CreateLecturerAccountData
{
    public function __construct(
        public readonly string $lecturerCode,
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?string $phoneNumber,
        public readonly ?string $academicTitle,
        public readonly ?int $degreeId,
        public readonly ?int $academicRankId,
        public readonly bool $isActive,
        public readonly int $departmentId,
        public readonly int $facultyId,
        public readonly int $creatorUserId,
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
