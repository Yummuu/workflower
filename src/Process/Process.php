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

use Yummuu\Workflower\Workflow\Activity\ActivityInterface;
use Yummuu\Workflower\Workflow\Activity\UnexpectedActivityStateException;
use Yummuu\Workflower\Workflow\Event\StartEvent;
use Yummuu\Workflower\Workflow\Operation\OperationRunnerInterface;
use Yummuu\Workflower\Workflow\ProcessInstance;
use Yummuu\Workflower\Workflow\WorkflowRepositoryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Process
{
    /**
     * @var int|string|WorkflowContextInterface
     */
    private $workflowContext;

    /**
     * @var WorkflowRepositoryInterface
     */
    private $workflowRepository;

    /**
     * @var ExpressionLanguage
     *
     * @since Property available since Release 1.2.0
     */
    private $expressionLanguage;

    /**
     * @var OperationRunnerInterface
     *
     * @since Property available since Release 1.2.0
     */
    private $operationRunner;

    /**
     * @param int|string|WorkflowContextInterface $workflowContext
     * @param WorkflowRepositoryInterface         $workflowRepository
     * @param OperationRunnerInterface            $operationRunner
     */
    public function __construct($workflowContext, WorkflowRepositoryInterface $workflowRepository, OperationRunnerInterface $operationRunner)
    {
        $this->workflowContext = $workflowContext;
        $this->workflowRepository = $workflowRepository;
        $this->operationRunner = $operationRunner;
    }

    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @since Method available since Release 1.2.0
     */
    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param EventContextInterface $eventContext
     */
    public function start(EventContextInterface $eventContext)
    {
        assert($eventContext->getProcessContext() !== null);
        assert($eventContext->getProcessContext()->getProcessInstance() === null);
        assert($eventContext->getEventId() !== null);

        $processInstance = $this->configureWorkflow($this->createWorkflow());
        $eventContext->getProcessContext()->setProcessInstance($processInstance);
        $processInstance->setProcessData($eventContext->getProcessContext()->getProcessData());
        $flowObject = $processInstance->getFlowObject($eventContext->getEventId());
        $processInstance->start(/* @var $flowObject StartEvent */ $flowObject);
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function allocateWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getProcessInstance() !== null);
        assert($workItemContext->getActivityId() !== null);

        $processInstance = $this->configureWorkflow($workItemContext->getProcessContext()->getProcessInstance());
        $flowObject = $processInstance->getFlowObject($workItemContext->getActivityId());
        $processInstance->allocateWorkItem(/* @var $flowObject ActivityInterface */ $flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function startWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getProcessInstance() !== null);
        assert($workItemContext->getActivityId() !== null);

        $processInstance = $this->configureWorkflow($workItemContext->getProcessContext()->getProcessInstance());
        $flowObject = $processInstance->getFlowObject($workItemContext->getActivityId());
        $processInstance->startWorkItem(/* @var $flowObject ActivityInterface */ $flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     */
    public function completeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getProcessInstance() !== null);
        assert($workItemContext->getActivityId() !== null);

        $processInstance = $this->configureWorkflow($workItemContext->getProcessContext()->getProcessInstance());
        $processInstance->setProcessData($workItemContext->getProcessContext()->getProcessData());
        $flowObject = $processInstance->getFlowObject($workItemContext->getActivityId());
        $processInstance->completeWorkItem(/* @var $flowObject ActivityInterface */ $flowObject, $workItemContext->getParticipant());
    }

    /**
     * @param WorkItemContextInterface $workItemContext
     *
     * @throws UnexpectedActivityStateException
     */
    public function executeWorkItem(WorkItemContextInterface $workItemContext)
    {
        assert($workItemContext->getProcessContext() !== null);
        assert($workItemContext->getProcessContext()->getProcessInstance() !== null);
        assert($workItemContext->getActivityId() !== null);
        assert($workItemContext->getProcessContext()->getProcessInstance()->getFlowObject($workItemContext->getActivityId()) instanceof ActivityInterface);

        $activity = $workItemContext->getProcessContext()->getProcessInstance()->getFlowObject($workItemContext->getActivityId()); /* @var $activity ActivityInterface */
        if ($activity->isAllocatable()) {
            $this->allocateWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getProcessContext()->getProcessInstance()->getCurrentFlowObject()->getId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isStartable()) {
            $this->startWorkItem($workItemContext);
            $nextWorkItemContext = new WorkItemContext($workItemContext->getParticipant());
            $nextWorkItemContext->setActivityId($workItemContext->getProcessContext()->getProcessInstance()->getCurrentFlowObject()->getId());
            $nextWorkItemContext->setProcessContext($workItemContext->getProcessContext());

            return $this->executeWorkItem($nextWorkItemContext);
        } elseif ($activity->isCompletable()) {
            $this->completeWorkItem($workItemContext);
        } else {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not executable.', $activity->getId()));
        }
    }

    /**
     * @return int|string|WorkflowContextInterface
     *
     * @since Method available since Release 1.1.0
     */
    public function getWorkflowContext()
    {
        return $this->workflowContext;
    }

    /**
     * @return ProcessInstance
     *
     * @throws WorkflowNotFoundException
     */
    private function createWorkflow()
    {
        $workflowId = $this->workflowContext instanceof WorkflowContextInterface ? $this->workflowContext->getWorkflowId() : $this->workflowContext;
        $processInstance = $this->workflowRepository->findById($workflowId);
        if ($processInstance === null) {
            throw new WorkflowNotFoundException(sprintf('The processInstance "%s" is not found.', $workflowId));
        }

        return $processInstance;
    }

    /**
     * @param ProcessInstance $processInstance
     *
     * @return ProcessInstance
     *
     * @since Method available since Release 1.2.0
     */
    private function configureWorkflow(ProcessInstance $processInstance)
    {
        if ($this->expressionLanguage !== null) {
            $processInstance->setExpressionLanguage($this->expressionLanguage);
        }

        if ($this->operationRunner !== null) {
            $processInstance->setOperationRunner($this->operationRunner);
        }

        return $processInstance;
    }
}
