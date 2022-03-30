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

namespace Yummuu\Workflower\Definition;

use Yummuu\Workflower\Workflow\WorkflowRepository;
use PHPUnit\Framework\TestCase;
use Yummuu\Workflower\Workflow\OperationRunnerDespatcher;

class Bpmn2ReaderTest extends TestCase
{
    /**
     * @test
     */
    public function read()
    {
        $workflowRepository = new WorkflowRepository();
        $bpmn2Reader = new Bpmn2Reader();
        $definitions = $bpmn2Reader->read(dirname(__DIR__) . '/Resources/config/workflower/LoanRequestProcess.bpmn');

        $instance = $definitions[0]->createProcessInstance();
        $dest = $workflowRepository->findById('LoanRequestProcess');
        $definitions[0]->setProcessDefinitions($dest->getProcessDefinition()->getProcessDefinitions());

        $this->assertThat($instance, $this->equalTo($dest));
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function readSource()
    {
        $workflowRepository = new WorkflowRepository();
        $bpmn2Reader = new Bpmn2Reader();
        $definitions = $bpmn2Reader->readSource(file_get_contents(dirname(__DIR__) . '/Resources/config/workflower/LoanRequestProcess.bpmn'));

        $instance = $definitions[0]->createProcessInstance();
        $dest = $workflowRepository->findById('LoanRequestProcess');
        $definitions[0]->setProcessDefinitions($dest->getProcessDefinition()->getProcessDefinitions());

        $this->assertThat($instance, $this->equalTo($dest));
    }

    public function testReadSourceAndGetServiceTasks()
    {
        $import      = new Bpmn2Reader();
        $definitions = $import->readSource(file_get_contents(dirname(__DIR__) . '/Resources/config/workflower/CamundaTest1.bpmn'));
        $item        = current($definitions);
        var_dump($item);
        $this->assertIsObject($item);
    }

    public function testProcessStart()
    {
        $repository = new ProcessDefinitionRepository();
        $repository->importFromSource(file_get_contents(dirname(__DIR__) . '/Resources/config/workflower/CamundaTest1.bpmn'));
        $process = $repository->getLatestById('Process_0zi0j0a')->createProcessInstance();
        $process->setOperationRunner(new OperationRunnerDespatcher());
        $process->setProcessData([]);
        $process->start($process->getFirstStartEvent());
        $reponse = $process->getProcessData();
        $this->assertTrue($reponse);
    }


    public function testReadDMN()
    {
        $import     = new DmnReader();
        $definition = $import->readSource(file_get_contents(dirname(__DIR__) . '/Resources/config/workflower/DemoDmn.dmn'));
        file_put_contents(dirname(__DIR__) . '/Runtime/DMN.json', $definition->toJson());
        $this->assertIsArray($definition->toArray());
    }

    public function testOutputDMNXML()
    {
        $import     = new DmnReader();
        $definition = $import->readSource(file_get_contents(dirname(__DIR__) . '/Resources/config/workflower/DemoDmn.dmn'));
        file_put_contents(dirname(__DIR__) . '/Runtime/output.xml', $definition->toXml());
        $this->assertIsArray($definition->toArray());
    }
}
