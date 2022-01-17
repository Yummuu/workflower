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

namespace Yummuu\Workflower\Workflow\Operation;

use Yummuu\Workflower\Workflow\Activity\WorkItemInterface;
use Yummuu\Workflower\Workflow\Participant\ParticipantInterface;
use Yummuu\Workflower\Workflow\ProcessInstance;

/**
 * @since Interface available since Release 1.2.0
 */
interface OperationRunnerInterface
{
    /**
     * @param OperationalInterface $operational
     * @param ProcessInstance             $processInstance
     *
     * @return ParticipantInterface
     */
    public function provideParticipant(OperationalInterface $operational, ProcessInstance $processInstance);

    /**
     * @param WorkItemInterface $workItem
     *
     * @return void
     */
    public function run(WorkItemInterface $workItem);
}
