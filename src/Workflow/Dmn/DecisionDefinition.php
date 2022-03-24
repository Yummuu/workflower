<?php

namespace Yummuu\Workflower\Workflow\Dmn;

class DecisionDefinition
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $name = null;
    /**
     * @var array
     */
    private $sequenceFlows = [];
    /**
     * @var DecisionTableDefinition
     */
    private $decisionTable ;
    /**
     * @var array
     */
    private $requirements = [];
    /**
     * @var string
     */
    private $literalExpression = '';

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }
}
