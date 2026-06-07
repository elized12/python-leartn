<?php

namespace App\Service\Course\Block\Validation;

use App\Service\Course\TypeBlock;

class Factory
{
    public function create(TypeBlock $type): BlockValidatorInterface
    {
        return match ($type) {
            TypeBlock::Video => new Video(),
            TypeBlock::ExecutableCode => new ExecutableCode(),
            TypeBlock::Divider => new Divider(),
            TypeBlock::InfoBox => new InfoBox(),
            TypeBlock::Image => new Image(),
            TypeBlock::TaskList => new TaskList(),
            TypeBlock::Quiz => new Quiz(),
            TypeBlock::Text => new Text(),
            default => throw new \InvalidArgumentException("Валидатор для типа блока '{$type->value}' не найден")
        };
    }

    public function createByString(string $type): BlockValidatorInterface
    {
        $typeBlock = TypeBlock::tryFrom($type);

        if (!$typeBlock) {
            throw new \InvalidArgumentException("Неизвестный тип блока: '{$type}'");
        }

        return $this->create($typeBlock);
    }
}
