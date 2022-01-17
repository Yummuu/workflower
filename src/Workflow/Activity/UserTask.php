<?php

namespace Yummuu\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class UserTask extends OperationalTask
{
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
