<?php

namespace Yummuu\Workflower\Workflow\Activity;

class ServiceTask extends OperationalTask
{
    private $camundaExpression;    //details expression  camunda:expression

    private $camundaTopic;         //details type camunda:topic

    public function getTaskExpression()
    {
        return $this->camundaExpression;
    }

    public function getTaskTopic()
    {
        return $this->camundaTopic;
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
            'camundaExpression'     => $this->camundaExpression,
            'camundaTopic'          => $this->camundaTopic,
        ]);
    }
}
