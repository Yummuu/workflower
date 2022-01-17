<?php
/*
 * Copyright (c) Atsuhiro Kubo <kubo@iteman.jp> and contributors,
 * All rights reserved.p
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Yummuu\Workflower\Workflow\Event;

use Yummuu\Workflower\Workflow\Connection\SequenceFlow;
use Yummuu\Workflower\Workflow\Element\ConnectingObjectInterface;
use Yummuu\Workflower\Workflow\Element\TransitionalInterface;
use Yummuu\Workflower\Workflow\SequenceFlowNotSelectedException;

class StartEvent extends Event implements TransitionalInterface, \Serializable
{
    /**
     * @var \DateTime
     *
     * @since Property available since Release 2.0.0
     */
    private $startDate;

    /**
     * {@inheritdoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'startDate' => $this->startDate,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::unserialize($value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function start(): void
    {
        parent::start();
        $this->startDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        $selectedSequenceFlows = [];
        $processInstance = $this->getProcessInstance();

        // for each sequence flow that leaves a start event start a parallel token
        foreach ($processInstance->getConnectingObjectCollectionBySource($this) as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject instanceof SequenceFlow) {
                $selectedSequenceFlows[] = $connectingObject;
            }
        }

        if (count($selectedSequenceFlows) == 0) {
            throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $this->getId()));
        }

        foreach ($this->getToken() as $token) {
            $processInstance->removeToken($this, $token);
        }

        // if there are multiple sequence flows available then the processInstance runs in parallel
        foreach ($selectedSequenceFlows as $selectedSequenceFlow) {
            $token = $processInstance->generateToken($this);
            $selectedSequenceFlow->getDestination()->run($token);
        }

        parent::end();
    }
}
