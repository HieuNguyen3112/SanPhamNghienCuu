<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResearchActivityDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $detail = $this->route('detail');
        $this->merge(['detail' => $detail]);

        $nullableFields = [
            'journal_name',
            'issn',
            'doi',
            'article_url',
            'volume',
            'issue',
            'page_start',
            'page_end',
            'year',
            'keywords',
            'publisher',
            'approval_decision_no',
            'approval_decision_date',
            'isbn',
            'pages',
            'project_code',
            'decision_no',
            'decision_date',
            'funding',
            'start_month',
            'end_month',
            'conference_name',
            'location',
            'held_on',
        ];

        $input = $this->all();
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        $detail = $this->input('detail');

        $rules = [
            'detail' => ['required', Rule::in([
                'paper_details',
                'book_details',
                'project_details',
                'conference_details',
            ])],
        ];

        switch ($detail) {
            case 'paper_details':
                $rules += [
                    'journal_name' => ['nullable', 'string', 'max:255'],
                    'issn' => ['nullable', 'string', 'max:50'],
                    'doi' => ['nullable', 'string', 'max:100'],
                    'article_url' => ['nullable', 'string', 'max:500'],
                    'volume' => ['nullable', 'string', 'max:50'],
                    'issue' => ['nullable', 'string', 'max:50'],
                    'page_start' => ['nullable', 'integer', 'min:1'],
                    'page_end' => ['nullable', 'integer', 'min:1'],
                    'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'keywords' => ['nullable', 'string', 'max:500'],
                ];
                break;
            case 'book_details':
                $rules += [
                    'publisher' => ['required', 'string', 'max:255'],
                    'approval_decision_no' => ['nullable', 'string', 'max:100'],
                    'approval_decision_date' => ['nullable', 'date'],
                    'isbn' => ['nullable', 'string', 'max:50'],
                    'pages' => ['nullable', 'integer', 'min:1'],
                    'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                ];
                break;
            case 'project_details':
                $rules += [
                    'project_code' => ['nullable', 'string', 'max:100'],
                    'decision_no' => ['nullable', 'string', 'max:100'],
                    'decision_date' => ['nullable', 'date'],
                    'funding' => ['nullable', 'numeric', 'min:0'],
                    'start_month' => ['nullable', 'date'],
                    'end_month' => ['nullable', 'date'],
                ];
                break;
            case 'conference_details':
                $rules += [
                    'conference_name' => ['required', 'string', 'max:255'],
                    'location' => ['nullable', 'string', 'max:255'],
                    'held_on' => ['nullable', 'date'],
                ];
                break;
        }

        return $rules;
    }
}
