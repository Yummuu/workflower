<?php

namespace Yummuu\Workflower\Workflow;

use Yummuu\Workflower\Participants\DefaultRoleParticipant;
use Yummuu\Workflower\Workflow\Activity\BusinessRuleTask;
use Yummuu\Workflower\Workflow\Activity\ServiceTask;
use Yummuu\Workflower\Workflow\Activity\UserTask;
use Yummuu\Workflower\Workflow\Activity\WorkItemInterface;
use Yummuu\Workflower\Workflow\Operation\OperationalInterface;
use Yummuu\Workflower\Workflow\Operation\OperationRunnerInterface;

class OperationRunnerDespatcher implements OperationRunnerInterface
{

    public function provideParticipant(OperationalInterface $operational, ProcessInstance $processInstance)
    {
        return new DefaultRoleParticipant();
    }

    public function run(WorkItemInterface $workItem)
    {
        /**
         * @var OperationalTask
         */
        $operational = $workItem->getActivity();
        var_dump($operational->getId());
        var_dump($operational->getRole());  //获取角色
        // $id       = $operational->getId();
        // $name     = $operational->getName();
        // $data     = $operational->getProcessInstance()->getProcessData();
        //不同的task执行不同的操作
        var_dump(get_class($operational));
        if ($operational instanceof BusinessRuleTask) {
            //决策表
            var_dump('ref:'.$operational->getDecisionRef());
        }
        if ($operational instanceof UserTask) {
            //用户填表
            var_dump($operational->getFormData());
        }
        if($operational instanceof ServiceTask) {
            //操作service
            var_dump($operational->getTaskTopic());
        }

    }
}
