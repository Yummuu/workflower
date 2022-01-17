<?php

namespace Yummuu\Workflower\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {

        return [
            ExpressionFunction::fromPhp('in_array'),
            ExpressionFunction::fromPhp('ceil'),
            ExpressionFunction::fromPhp('strtoupper'),
            ExpressionFunction::fromPhp('strtolower'),
            ExpressionFunction::fromPhp('round'),
            ExpressionFunction::fromPhp('max'),
            ExpressionFunction::fromPhp('min'),
            ExpressionFunction::fromPhp('array_change_key_case'),
        ];
    }
}
