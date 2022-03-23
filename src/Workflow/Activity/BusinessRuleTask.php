<?php

namespace Yummuu\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class BusinessRuleTask extends OperationalTask
{
    private $camundaDecisionRef;         //details decisionRef 

    public function getDecisionRef()
    {
        return $this->camundaDecisionRef;
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'camundaDecisionRef'    => $this->camundaDecisionRef
        ]);
    }
}
