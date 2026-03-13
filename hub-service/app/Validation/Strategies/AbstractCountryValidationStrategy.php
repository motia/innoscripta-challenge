<?php

namespace App\Validation\Strategies;

use App\Validation\Fields\Field;

abstract class AbstractCountryValidationStrategy implements CountryValidationStrategy
{
    /** @var Field[] */
    private array $fieldsCache = [];

    /**
     * Concrete strategies must provide their field definitions.
     *
     * @return Field[]
     */
    abstract protected function defineFields(): array;

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        if (empty($this->fieldsCache)) {
            $this->fieldsCache = $this->defineFields();
        }

        return $this->fieldsCache;
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $rules[$field->name()] = $field->validationRules();
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];

        foreach ($this->fields() as $field) {
            $messages = array_merge($messages, $field->validationMessages());
        }

        return $messages;
    }

    public function checklistRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if (!$field->contributesToChecklist()) {
                continue;
            }

            $label = $field->checklistLabel() ?? $this->humanize($field->name());

            $rules[$field->name()] = [
                'field' => $field->name(),
                'label' => $label,
                'rule' => $field->checklistRule(),
                'message' => $field->checklistMessage() ?? ($label . ' is required'),
            ];
        }

        return $rules;
    }

    public function customFields(): array
    {
        $custom = [];

        foreach ($this->fields() as $field) {
            if ($field->isCustomField()) {
                $custom[] = $field->name();
            }
        }

        return $custom;
    }

    public function listColumns(): array
    {
        $base = ['id', 'name', 'last_name', 'salary', 'country'];

        foreach ($this->customFields() as $fieldName) {
            if (!in_array($fieldName, $base, true)) {
                $base[] = $fieldName;
            }
        }

        return $base;
    }

    public function extractCustomFields(array $employeeData): array
    {
        $fields = [];

        foreach ($this->customFields() as $fieldName) {
            if (array_key_exists($fieldName, $employeeData)) {
                $fields[$fieldName] = $employeeData[$fieldName];
            }
        }

        return $fields;
    }

    private function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
