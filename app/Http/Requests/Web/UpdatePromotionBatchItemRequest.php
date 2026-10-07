<?php

namespace App\Http\Requests\Web;

use App\Models\PromotionBatchItem;
use App\Models\Role;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePromotionBatchItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in([
                PromotionBatchItem::ACTION_PROMOTE,
                PromotionBatchItem::ACTION_RETAIN,
                PromotionBatchItem::ACTION_EXCLUDE,
                PromotionBatchItem::ACTION_GRADUATE,
            ])],
            'target_grade_id' => [
                'nullable',
                'integer',
                'exists:grades,id',
                'required_if:action,promote,retain',
                'prohibited_unless:action,promote,retain',
            ],
            'target_section_id' => [
                'nullable',
                'integer',
                'exists:sections,id',
                'required_if:action,promote,retain',
                'prohibited_unless:action,promote,retain',
            ],
        ];
    }

    /**
     * The target section must be a section of the chosen target grade.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['action', 'target_grade_id', 'target_section_id'])) {
                    return;
                }

                if (! in_array($this->input('action'), [PromotionBatchItem::ACTION_PROMOTE, PromotionBatchItem::ACTION_RETAIN], true)) {
                    return;
                }

                $targetSection = Section::query()->find($this->integer('target_section_id'));

                if ($targetSection?->grade_id !== $this->integer('target_grade_id')) {
                    $validator->errors()->add('target_section_id', 'The target section must belong to the selected target grade.');
                }
            },
        ];
    }
}
