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
            'journal_scope',
            'journal_source_name',
            'journal_publisher',
            'journal_website',
            'work_score',
            'doi',
            'article_url',
            'volume',
            'issue',
            'page_start',
            'page_end',
            'year',
            'research_field',
            'publication_status',
            'keywords',
            'conference_name',
            'conference_level',
            'conference_research_field',
            'conference_organization',
            'conference_has_isbn',
            'conference_isbn',
            'conference_point',
            'publisher',
            'approval_decision_no',
            'approval_decision_date',
            'isbn',
            'pages',
            'project_code',
            'project_category',
            'research_field',
            'objectives',
            'content_summary',
            'application_address',
            'implementing_unit',
            'project_status',
            'main_results',
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

        if ($detail === 'paper_details') {
            $input['conference_has_isbn'] = (bool) ($input['conference_has_isbn'] ?? false);
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
                    'journal_scope' => ['nullable', 'string', 'max:100'],
                    'journal_source_name' => ['nullable', 'string', 'max:255'],
                    'journal_publisher' => ['nullable', 'string', 'max:255'],
                    'journal_website' => ['nullable', 'string', 'max:255'],
                    'work_score' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
                    'doi' => ['nullable', 'string', 'max:100'],
                    'article_url' => ['nullable', 'string', 'max:500'],
                    'volume' => ['nullable', 'string', 'max:50'],
                    'issue' => ['nullable', 'string', 'max:50'],
                    'page_start' => ['nullable', 'integer', 'min:1'],
                    'page_end' => ['nullable', 'integer', 'min:1'],
                    'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'research_field' => ['nullable', 'string', 'max:255'],
                    'publication_status' => ['nullable', 'string', 'max:100'],
                    'keywords' => ['nullable', 'string', 'max:500'],
                    'conference_name' => ['nullable', 'string', 'max:255'],
                    'conference_level' => ['nullable', 'string', Rule::in(['NATIONAL', 'INTERNATIONAL'])],
                    'conference_research_field' => ['nullable', 'string', 'max:255'],
                    'conference_organization' => ['nullable', 'string', 'max:255'],
                    'conference_has_isbn' => ['nullable', 'boolean'],
                    'conference_isbn' => ['nullable', 'string', 'max:50'],
                    'conference_point' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
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
                    'project_code' => ['required', 'string', 'max:100'],
                    'project_category' => ['nullable', 'string', 'max:255'],
                    'research_field' => ['nullable', 'string', 'max:255'],
                    'objectives' => ['nullable', 'string', 'max:2000'],
                    'content_summary' => ['nullable', 'string', 'max:3000'],
                    'application_address' => ['nullable', 'string', 'max:500'],
                    'implementing_unit' => ['required', 'string', 'max:255'],
                    'project_status' => ['required', 'string', 'max:100'],
                    'main_results' => ['required', 'string', 'max:3000'],
                    'decision_no' => ['nullable', 'string', 'max:100'],
                    'decision_date' => ['nullable', 'date'],
                    'funding' => ['nullable', 'numeric', 'min:0'],
                    'start_month' => ['required', 'date'],
                    'end_month' => ['required', 'date', 'after_or_equal:start_month'],
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
