<?php

namespace Yummuu\Workflower\Participants;

use Yummuu\Workflower\Workflow\Participant\ParticipantInterface;
use Yummuu\Workflower\Workflow\ProcessInstance;
use Yummuu\Workflower\Workflow\Resource\ResourceInterface;

class DefaultRoleParticipant implements ParticipantInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     */
    public function __construct()
    {
        $this->id = PHP_INT_MIN;
        $this->name = 'DefaultRoleParticipant';
    }

    public function getId()
    {
        return $this->id;
    }

    public function hasRole($role)
    {
        return $role === ProcessInstance::DEFAULT_ROLE_ID;
    }

    public function setResource(ResourceInterface $resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getName()
    {
        return $this->name;
    }
}
