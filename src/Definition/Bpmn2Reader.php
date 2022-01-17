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

use DOMXPath;
use Yummuu\Workflower\Workflow\ProcessDefinition;
use Yummuu\Workflower\Workflow\ProcessInstance;

class Bpmn2Reader
{
    /**
     * @param string $file
     * @param array $config
     * 
     * @return ProcessDefinition[]
     *
     * @throws IdAttributeNotFoundException
     */
    public function read($file, $config = [])
    {
        $document = new \DOMDocument();
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($file, $document) {
            $document->load($file);
        });
        $errorToExceptionContext->invoke();
        $this->dealXmlByXPath($document, $config);
        return $this->readDocument($document, pathinfo($file, PATHINFO_FILENAME));
    }

    /**
     * @param string $source
     * @param array $config     全局配置
     *
     * @return ProcessDefinition[]
     *
     * @throws IdAttributeNotFoundException
     *
     * @since Method available since Release 1.3.0
     */
    public function readSource($source, $config = [])
    {
        $document = new \DOMDocument();
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($source, $document) {
            $document->loadXML($source);
        });
        $errorToExceptionContext->invoke();
        $this->dealXmlByXPath($document, $config);
        return $this->readDocument($document);
    }

    /**
     * @param \DOMDocument $document
     * @param int|string   $workflowId
     *
     * @return ProcessDefinition[]
     *
     * @throws IdAttributeNotFoundException
     *
     * @since Method available since Release 1.3.0
     */
    private function readDocument(\DOMDocument $document, $workflowId = null)
    {
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($document) {
            $document->schemaValidate(dirname(__DIR__) . '/Resources/config/workflower/schema/BPMN20.xsd');
        });
        $errorToExceptionContext->invoke();

        $processes = [];
        $globalData = [
            'messages' => [],
            'operations' => [],
        ];

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'message') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $globalData['messages'][$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'operation') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $globalData['operations'][$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'process') as $element) {
            $processes[] = new ProcessDefinition($this->readProcess($globalData, $element));
        }

        return $processes;
    }

    /**
     * @param array       $globalData
     * @param \DOMElement $element
     *
     * @return array
     */
    private function readProcess(array $globalData, \DOMElement $rootElement)
    {
        if (!$rootElement->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
        }

        $process = [
            'id' => $rootElement->getAttribute('id'),
            'name' => $rootElement->hasAttribute('name') ? $rootElement->getAttribute('name') : null,
            'roles' => [],
            'objectRoles' => [],
        ];

        foreach ($rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'lane') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
            }

            $id = $element->getAttribute('id');

            $process['roles'][] = [
                'id' => $id,
                'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
            ];

            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'flowNodeRef') as $childElement) {
                $process['objectRoles'][$childElement->nodeValue] = $id;
            }
        }

        if (count($process['roles']) == 0) {
            $process['roles'][] = [
                'id' => ProcessInstance::DEFAULT_ROLE_ID,
            ];
        }

        $process['startEvents'] = $this->readEvents($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'startEvent'));
        $process['endEvents'] = $this->readEvents($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'endEvent'));

        $process['tasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'task'));
        $process['userTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'userTask'));
        $process['manualTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'manualTask'));
        $process['serviceTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'serviceTask'));
        $process['sendTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'sendTask'));
        $process['callActivities'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'callActivity'));

        foreach ($rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'subProcess') as $element) {
            $task = $this->readTask($globalData, $process, $element);
            $task['processDefinition'] = $this->readProcess($globalData, $element);
            $process['subProcesses'][] = $task;
        }

        $process['exclusiveGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'exclusiveGateway'));
        $process['parallelGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'parallelGateway'));
        $process['inclusiveGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'inclusiveGateway'));

        $process['sequenceFlows'] = $this->readSequenceFlows($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'sequenceFlow'));

        return $process;
    }

    /**
     * @param array        $globalData
     * @param array        $process
     * @param \DOMNodeList $nodes
     */
    private function readTasks(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            $items[] = $this->readTask($globalData, $process, $element);
        }

        return $items;
    }

    /**
     * @param array       $globalData
     * @param array       $process
     * @param \DOMElement $element
     */
    private function readTask(array $globalData, array $process, $element)
    {
        if (!$element->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
        }

        $id = $element->getAttribute('id');
        $message = $element->hasAttribute('messageRef') ? $globalData['messages'][$element->getAttribute('messageRef')] : null;
        $operation = $element->hasAttribute('operationRef') ? $globalData['operations'][$element->getAttribute('operationRef')] : null;
        $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;
        $calledElement = $element->hasAttribute('calledElement') ? $element->getAttribute('calledElement') : null;
        $multiInstance = null;
        $sequential = null;
        $completionCondition = null;

        foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'multiInstanceLoopCharacteristics') as $childElement) {
            $multiInstance = true;
            $sequential = $childElement->hasAttribute('isSequential') ? ($childElement->getAttribute('isSequential') === 'true') : false;
            foreach ($childElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'completionCondition') as $conditionElement) {
                $completionCondition = $conditionElement->nodeValue;
            }
        }

        // 添加对camunda 的支持
        $inputParams  = [];     //输入处理
        $outputParams = [];     //输出处理
        $formData     = [];     //表单处理
        if ($element->hasAttribute('camunda:formKey')) {
            $formData['formKey']         = $element->getAttribute('camunda:formKey');
            $formData['candidateUsers']  = $element->hasAttribute('camunda:candidateUsers') ? $element->getAttribute('camunda:candidateUsers') : null;
            $formData['candidateGroups'] = $element->hasAttribute('camunda:candidateGroups') ? $element->getAttribute('camunda:candidateGroups') : null;
            $formData['dueDate']         = $element->hasAttribute('camunda:dueDate') ? $element->getAttribute('camunda:dueDate') : null;
        }
        foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'extensionElements') as $childElement) {
            /**
             * @var \DOMElement
             */
            foreach ($childElement->getElementsByTagNameNs('http://camunda.org/schema/1.0/bpmn', 'inputOutput') as $inputOutputElement) {
                $inputParams = $this->analysisExtensionWithCamundaInputOutput($inputOutputElement, 'http://camunda.org/schema/1.0/bpmn', 'inputParameter');
                $outputParams = $this->analysisExtensionWithCamundaInputOutput($inputOutputElement, 'http://camunda.org/schema/1.0/bpmn', 'outputParameter');
            }
            /**
             * @var \DOMElement
             */
            foreach ($childElement->getElementsByTagNameNS('http://camunda.org/schema/1.0/bpmn', 'formData') as  $formDataElement) {
                $formFieldArr  = $this->analysisExtensionWithCamundaFormData($element, 'http://camunda.org/schema/1.0/bpmn');
                if ($formFieldArr) {
                    $formData['formField'] = $formFieldArr;
                }
            }
        }
        // 添加对camunda input的支持 end

        $config = [
            'id' => $id,
            'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
            'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id),
        ];

        if ($multiInstance !== null) {
            $config['multiInstance'] = $multiInstance;
        }
        if ($sequential !== null) {
            $config['sequential'] = $sequential;
        }
        if ($completionCondition !== null) {
            $config['completionCondition'] = $completionCondition;
        }
        if ($defaultSequenceFlowId !== null) {
            $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
        }
        if ($message !== null) {
            $config['message'] = $message;
        }
        if ($operation !== null) {
            $config['operation'] = $operation;
        }
        if ($calledElement !== null) {
            $config['calledElement'] = $calledElement;
        }

        if ($inputParams) {
            $config['inputParameters'] = $inputParams;
        }

        if ($outputParams) {
            $config['outputParameters'] = $outputParams;
        }

        if ($formData) {
            $config['formData'] = $formData;
        }

        return $config;
    }

    /**
     * @param array        $globalData
     * @param array        $process
     * @param \DOMNodeList $nodes
     */
    private function readGateways(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;

            $config = [
                'id' => $id,
                'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id),
            ];

            if ($defaultSequenceFlowId !== null) {
                $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
            }

            $items[] = $config;
        }

        return $items;
    }

    /**
     * @param array        $globalData
     * @param array        $process
     * @param \DOMNodeList $nodes
     */
    private function readEvents(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;
            $name = $element->hasAttribute('name') ? $element->getAttribute('name') : null;

            $config = [
                'id' => $id,
                'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id),
            ];

            if ($name !== null) {
                $config['name'] = $name;
            }
            if ($defaultSequenceFlowId !== null) {
                $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
            }

            $items[] = $config;
        }

        return $items;
    }

    /**
     * @param array        $globalData
     * @param array        $process
     * @param \DOMNodeList $nodes
     */
    private function readSequenceFlows(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $name = $element->hasAttribute('name') ? $element->getAttribute('name') : null;
            $condition = null;
            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'conditionExpression') as $childElement) {
                $condition = $childElement->nodeValue;
                break;
            }

            $config = [
                'id' => $id,
                'source' => $element->getAttribute('sourceRef'),
                'destination' => $element->getAttribute('targetRef'),
            ];

            if ($name !== null) {
                $config['name'] = $name;
            }
            if ($condition !== null) {
                $config['condition'] = $condition;
            }

            $items[] = $config;
        }

        return $items;
    }

    /**
     * @param \DOMElement $element
     * @param int|string  $processInstanceId
     *
     * @return IdAttributeNotFoundException
     */
    private function createIdAttributeNotFoundException(\DOMElement $element, $processInstanceId)
    {
        return new IdAttributeNotFoundException(sprintf('The id attribute of the "%s" element is not found in workflow "%s" on line %d', $element->tagName, $processInstanceId, $element->getLineNo()));
    }

    /**
     * @param array  $flowObjectRoles
     * @param string $flowObjectId
     *
     * @return string
     *
     * @since Method available since Release 1.3.0
     */
    private function provideRoleIdForFlowObject(array $flowObjectRoles, $flowObjectId)
    {
        return count($flowObjectRoles) ? $flowObjectRoles[$flowObjectId] : ProcessInstance::DEFAULT_ROLE_ID;
    }

    private function analysisExtensionWithCamundaInputOutput($element, $tag, $parmas = 'inputParameter')
    {
        $inputParams = [];
        /**
         * @var \DOMElement
         */
        foreach ($element->getElementsByTagNameNs($tag, $parmas) as $inputParamElement) {
            if (!$inputParamElement->hasAttribute('name')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no name', $inputParamElement->tagName));
            }
            if ($inputParamElement->hasChildNodes()) {
                $mapData = [];
                foreach ($inputParamElement->getElementsByTagNameNs($tag, 'map') as $mapElement) {
                    foreach ($mapElement->getElementsByTagNameNs($tag, 'entry') as $entryElement) {
                        if (!$entryElement->hasAttribute('key')) {
                            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no key', $entryElement->tagName));
                        }

                        $mapData[$entryElement->getAttribute('key')] =  $entryElement->nodeValue;
                    }
                }
                if ($mapData) {
                    $inputParams[$inputParamElement->getAttribute('name')] = $mapData;
                }

                $listData = [];
                foreach ($inputParamElement->getElementsByTagNameNs($tag, 'list') as $listElement) {
                    foreach ($listElement->getElementsByTagNameNs($tag, 'value') as $valueElement) {
                        $listData[] = $valueElement->nodeValue;
                    }
                }
                if ($listData) {
                    $inputParams[$inputParamElement->getAttribute('name')] = $listData;
                }

                if (!isset($inputParams[$inputParamElement->getAttribute('name')])) {
                    $inputParams[$inputParamElement->getAttribute('name')] = $inputParamElement->nodeValue;
                }
            }
        }
        return $inputParams;
    }

    /**
     * 处理camunda的form表单
     *
     * @param [type] $element
     * @return array
     */
    public function analysisExtensionWithCamundaFormData($element, $tag)
    {
        $formFieldArr = [];
        foreach ($element->getElementsByTagNameNs($tag, 'formField') as $formFieldElement) {
            if (!$formFieldElement->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $formFieldElement->tagName));
            }
            $tmpFieldArr  = [
                'id'           => $formFieldElement->getAttribute('id'),
                'label'        => $formFieldElement->hasAttribute('label') ? $formFieldElement->getAttribute('label') : null,
                'type'         => $formFieldElement->hasAttribute('type') ? $formFieldElement->getAttribute('type') : null,
                'defaultValue' => $formFieldElement->hasAttribute('defaultValue') ? $formFieldElement->getAttribute('defaultValue') : null,
            ];
            if ($formFieldElement->hasChildNodes()) {
                $values = [];
                foreach ($formFieldElement->getElementsByTagNameNs($tag, 'value') as $valueElement) {
                    $values[] = [
                        'id' => $valueElement->hasAttribute('id') ? $valueElement->getAttribute('id') : null,
                        'name' => $valueElement->hasAttribute('name') ? $valueElement->getAttribute('name') : null
                    ];
                }
                $validation = [];
                foreach ($formFieldElement->getElementsByTagNameNs($tag, 'validation') as $validationElement) {
                    foreach ($validationElement->getElementsByTagNameNs($tag, 'constraint') as $constraintElement) {
                        $validation[] = [
                            'config' => $constraintElement->hasAttribute('config') ? $constraintElement->getAttribute('config') : null,
                            'name' => $constraintElement->hasAttribute('name') ? $constraintElement->getAttribute('name') : null
                        ];
                    }
                }
                $property   = [];
                foreach ($formFieldElement->getElementsByTagNameNs($tag, 'properties') as $propertiesElement) {
                    foreach ($propertiesElement->getElementsByTagNameNs($tag, 'property') as $propertyElement) {
                        $property[] = [
                            'id' => $propertyElement->hasAttribute('id') ? $propertyElement->getAttribute('id') : null,
                            'value' => $constraintElement->hasAttribute('value') ? $propertyElement->getAttribute('value') : null
                        ];
                    }
                }
                if ($values) {
                    $tmpFieldArr['values'] = $values;
                }
                if ($validation) {
                    $tmpFieldArr['validation'] = $validation;
                }
                if ($property) {
                    $tmpFieldArr['properties'] = $property;
                }
            }
            $formFieldArr[] = $tmpFieldArr;
        }
        return $formFieldArr;
    }

    public function dealXmlByXPath(\DOMDocument $document, $data = [])
    {
        $xpath = new DOMXPath($document);
        foreach ($data as $name => $config) {
            $str = "//bpmn:serviceTask[@name='$name']";
            $serviceTasks = $xpath->query($str);
            if (is_null($serviceTasks) || $serviceTasks->length == 0) {
                continue; //没有这个task,就不处理
            }
            foreach ($serviceTasks as $taskNode) {
                if (!$taskNode->hasAttribute('id')) {
                    continue; //没有id的task是错误的
                }
                $taskNodeStr = "//bpmn:serviceTask[@id='" . $taskNode->getAttribute('id') . "']";
                foreach ($config as $item) {
                    if ($item['type'] != 'inputParameter') {
                        continue; //目前还不支持其它参数
                    }
                    $inputNode = $xpath->query($taskNodeStr . "/bpmn:extensionElements/camunda:inputOutput/camunda:inputParameter[@name='" . $item['name'] . "']");
                    if (!is_null($inputNode) && $inputNode->length > 0) {
                        $inputNode = $inputNode[0];
                        //说明有node
                        $parentNode = $inputNode->parentNode;
                        $parentNode->removeChild($inputNode);   //返回old child
                    } else {
                        //说明没有节点
                        //要判断是否有extensionElements和inputOutput节点
                        $parentElements = $xpath->query($taskNodeStr . "/bpmn:extensionElements/camunda:inputOutput");
                        if (is_null($parentElements) || $parentElements->length == 0) {
                            //没有inputOutput，那就找extensionElements
                            $extensionElements = $xpath->query($taskNodeStr . "/bpmn:extensionElements");
                            if (is_null($extensionElements) || $extensionElements->length == 0) {
                                //extensionElements也没有
                                $extensionNew = $document->createElementNS("http://www.omg.org/spec/BPMN/20100524/MODEL", 'extensionElements');
                                $parentNode = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", 'inputOutput');
                                $extensionNew->appendChild($parentNode);

                                $taskNode->appendChild($extensionNew);
                            } else {
                                $extensionElements = $extensionElements[0];
                                $parentNode = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", 'inputOutput');
                                $extensionElements->appendChild($parentNode);
                            }
                        } else {
                            $parentNode = $parentElements[0];
                        }
                        //end- 这里得到的parentNode 是个空的或没有指定child的，所以就不用再removeChild了
                    }
                    //开始新增input
                    $inputNewNode = $this->createInputParameterNode($document, $item['name'], $item['nodeType'], $item['node']);
                    $parentNode->appendChild($inputNewNode);
                };
            }
        }
    }

    protected function createInputParameterNode(\DOMDocument $document, $name, $type, $node)
    {
        $inputNewNode = $document->createElementNS("http://camunda.org/schema/1.0/bpmn", "inputParameter");
        $inputNewNode->setAttribute("name", $name);
        switch ($type) {
            case 'map':
                if (is_array($node) && count($node) > 0) {
                    $map = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", "map");
                    foreach ($node as $key => $value) {
                        $child = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", "entry", $value);
                        $child->setAttribute("key", $key);
                        $map->appendChild($child);
                    }
                    $inputNewNode->appendChild($map);
                }
                break;
            case 'list':
                if (is_array($node) && count($node) > 0) {
                    $nodeList = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", "list");
                    foreach ($node as $key => $value) {
                        $child = $document->createElementNs("http://camunda.org/schema/1.0/bpmn", "value", $value);
                        $nodeList->appendChild($child);
                    }
                    $inputNewNode->appendChild($nodeList);
                }
                break;
            default:
                $inputNewNode->nodeValue = (string)$node;
                break;
        }
        return $inputNewNode;
    }
}
