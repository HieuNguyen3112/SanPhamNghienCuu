<?php

namespace App\Http\Requests\Faculty\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesFacultyScopedUnit
{
    protected function addFacultyScopedUnitValidation(Validator $validator, string $field = 'unit_id'): void
    {
        $validator->after(function (Validator $validator) use ($field) {
            if ($validator->errors()->has($field)) {
                return;
            }

            $unitId = $this->input($field);
            if ($unitId === null || $unitId === '') {
                return;
            }

            $facultyId = $this->resolveFacultyScopeId();
            if (! $facultyId) {
                return;
            }

            $inScope = DB::table('departments')
                ->where('id', (int) $unitId)
                ->where('faculty_id', $facultyId)
                ->exists();

            if (! $inScope) {
                $validator->errors()->add($field, 'Đơn vị không thuộc phạm vi khoa hiện tại.');
            }
        });
    }

    private function resolveFacultyScopeId(): ?int
    {
        $departmentId = $this->user()?->lecturer?->department_id;
        if (! $departmentId) {
            return null;
        }

        $facultyId = DB::table('departments')
            ->where('id', (int) $departmentId)
            ->value('faculty_id');

        return $facultyId ? (int) $facultyId : null;
    }
}
