<?php
/*
 * Copyright (c) Atsuhiro Kubo <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Yummuu\Workflower\Process;

class EventContext implements EventContextInterface
{
    /**
     * @var int|string
     */
    private $eventId;

    /**
     * @var ProcessContextInterface
     */
    private $processContext;

    /**
     * @param int|string              $eventId
     * @param ProcessContextInterface $processContext
     */
    public function __construct($eventId, ProcessContextInterface $processContext)
    {
        $this->eventId = $eventId;
        $this->processContext = $processContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessContext()
    {
        return $this->processContext;
    }
}
