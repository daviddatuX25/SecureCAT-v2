<?php

namespace App\ValueObjects;

readonly class DocxValidationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly array $found,
        public readonly array $missing,
        public readonly array $extra,
    ) {}

    /**
     * @return array{valid: bool, found: string[], missing: string[], extra: string[]}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'found' => $this->found,
            'missing' => $this->missing,
            'extra' => $this->extra,
        ];
    }
}
