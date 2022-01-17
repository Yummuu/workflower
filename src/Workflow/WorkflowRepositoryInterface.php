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

namespace Yummuu\Workflower\Workflow;

interface WorkflowRepositoryInterface
{
    /**
     * @param int|string $id
     *
     * @return ProcessInstance|null
     */
    public function findById($id): ?ProcessInstance;

    /**
     * @param ProcessInstance $processInstance
     */
    public function add($processInstance): void;

    /**
     * @param ProcessInstance $processInstance
     */
    public function remove($processInstance): void;
}
