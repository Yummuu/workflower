<?php

namespace Yummuu\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class UserTask extends OperationalTask
{
    private $formData = [];

    public function getFormData()
    {
        return $this->formData;
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
            'formData'              => $this->formData
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function equals($target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }
}
