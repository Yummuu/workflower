<?php

namespace Yummuu\Workflower\Expression;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        // prepends the default provider to let users override it
        array_unshift($providers, new ExpressionLanguageProvider());

        parent::__construct($cache, $providers);
    }
}
