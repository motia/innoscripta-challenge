<?php

namespace App\Validation\Fields;

abstract class AbstractField implements Field
{
    public function __construct(
        protected string $name,
        protected array $rules,
        protected array $messages = [],
        protected ?string $checklistRule = null,
        protected ?string $checklistLabel = null,
        protected ?string $checklistMessage = null,
        protected bool $isCustomField = false
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function validationRules(): array
    {
        return $this->rules;
    }

    public function validationMessages(): array
    {
        return $this->messages;
    }

    public function contributesToChecklist(): bool
    {
        return $this->checklistRule !== null;
    }

    public function checklistLabel(): ?string
    {
        return $this->checklistLabel;
    }

    public function checklistRule(): ?string
    {
        return $this->checklistRule;
    }

    public function checklistMessage(): ?string
    {
        return $this->checklistMessage;
    }

    public function isCustomField(): bool
    {
        return $this->isCustomField;
    }
}
