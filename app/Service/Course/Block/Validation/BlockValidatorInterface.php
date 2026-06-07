<?php

namespace App\Service\Course\Block\Validation;

interface BlockValidatorInterface
{
    public function validate(string $block): array;
}
