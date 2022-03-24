<?php

namespace Yummuu\Workflower\Definition;

use Yummuu\Workflower\Workflow\Dmn\DecisionDefinition;
use Yummuu\Workflower\Workflow\Dmn\DecisionTableDefinition;
use Yummuu\Workflower\Workflow\Dmn\DRDDefinition;

class DmnReader
{
    /**
     * @param string $source
     *
     * @return DRDDefinition
     *
     * @throws IdAttributeNotFoundException
     * 
     */
    public function readSource($source): DRDDefinition
    {
        $document = new \DOMDocument();
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($source, $document) {
            $document->loadXML($source);
        });
        $errorToExceptionContext->invoke();
        return $this->readDocument($document);
    }

    /**
     *
     * @param \DOMDocument $document
     * @return DRDDefinition
     */
    protected function readDocument(\DOMDocument $document): DRDDefinition
    {
        $drd = [
            'id'   => '',
            'name' => '',
            'inputData'              => $this->readOtherByTag($document, 'inputData'),
            'businessKnowledgeModel' => $this->readOtherByTag($document, 'businessKnowledgeModel'),
            'knowledgeSource'        => $this->readOtherByTag($document, 'knowledgeSource'),
        ];
        foreach ($document->getElementsByTagName('decision') as $element) {
            $drd['decisions'][] = $this->readDecision($element);
        }
        return new DRDDefinition($drd);
    }

    /**
     * 决策表读取
     *
     * @param \DOMElement $rootElement
     * @return DecisionDefinition
     */
    protected function readDecision(\DOMElement $rootElement): DecisionDefinition
    {
        if (!$rootElement->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
        }

        $decision  = [
            'id'   => $rootElement->getAttribute('id'),
            'name' => $rootElement->hasAttribute('name') ? $rootElement->getAttribute('name') : null,
            'requirements' => $this->readRequirements($rootElement)
        ];
        foreach ($rootElement->getElementsByTagName('decisionTable') as $element) {
            $decision['decisionTable'] = $this->readDecisionTable($element);
        }
        foreach ($rootElement->getElementsByTagName('literalExpression') as $element) {
            $decision['literalExpression'] = $element->nodeValue;
        }
        return new DecisionDefinition($decision);
    }

    /**
     * 决策表读取
     *
     * @param \DOMElement $rootElement
     * @return DecisionTableDefinition
     */
    protected function readDecisionTable(\DOMElement $rootElement): DecisionTableDefinition
    {
        if (!$rootElement->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
        }
        $table =  [
            'id'        => $rootElement->getAttribute('id'),
            'hitPolicy' => $rootElement->hasAttribute('hitPolicy') ? $rootElement->getAttribute('hitPolicy') : null,
        ];
        //input
        foreach ($rootElement->getElementsByTagName('input') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }
            $input = [
                'id'    => $element->getAttribute('id'),
                'label' => $element->hasAttribute('label') ? $element->getAttribute('label') : '',
            ];
            foreach ($element->getElementsByTagName('inputExpression') as $childElement) {
                $input['inputExpressionId']   = $childElement->getAttribute('id');
                $input['inputExpressionType'] = $childElement->hasAttribute('typeRef') ? $childElement->getAttribute('typeRef') : '';
                $input['inputExpression']     = $childElement->textContent;
            }
            foreach ($element->getElementsByTagName('inputValues') as $childElement) {
                $input['inputValuesId'] = $childElement->getAttribute('id');
                $input['inputValues']   = $childElement->textContent;
            }
            $table['input'][] = $input;
        }

        //output
        foreach ($rootElement->getElementsByTagName('output') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }
            $output = [
                'id'      => $element->getAttribute('id'),
                'label'   => $element->hasAttribute('label') ? $element->getAttribute('label') : '',
                'name'    => $element->hasAttribute('name') ? $element->getAttribute('name') : '',
                'typeRef' => $element->hasAttribute('typeRef') ? $element->getAttribute('typeRef') : '',

            ];
            foreach ($element->getElementsByTagName('outputValues') as $childElement) {
                $output['inputValuesId'] = $childElement->getAttribute('id');
                $output['inputValues']   = $childElement->textContent;
            }
            $table['output'][] = $output;
        }

        //rules
        foreach ($rootElement->getElementsByTagName('rule') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }
            $rule = [
                'id'      => $element->getAttribute('id'),
            ];
            foreach ($element->getElementsByTagName('description') as $childElement) {
                $rule['description']   = $childElement->nodeValue;
            }
            $index = 0;
            $input = [];
            foreach ($element->getElementsByTagName('inputEntry') as $childElement) {
                if (!isset($table['input'][$index])) {
                    throw new IdAttributeNotFoundException(sprintf('index-"%s" input is not set', $index));
                }
                $titleId  = $table['input'][$index]['id'];
                $input[$titleId] = trim($childElement->nodeValue);
                $index++;
            }
            $rule['input'] = $input;

            $index  = 0;
            $output = [];
            foreach ($element->getElementsByTagName('outputEntry') as $childElement) {
                if (!isset($table['output'][$index])) {
                    throw new IdAttributeNotFoundException(sprintf('index-"%s" output is not set', $index));
                }
                $titleId         = $table['output'][$index]['id'];
                $output[$titleId] = trim($childElement->nodeValue);
                $index++;
            }
            $rule['output']   = $output;
            $table['rules'][] = $rule;
        }

        return new DecisionTableDefinition($table);
    }

    /**
     *
     * @param \DOMDocument $rootElement
     * @param string $tagName
     * @return array
     */
    protected function readOtherByTag(\DOMDocument $rootElement, $tagName = '')
    {
        $array = [];
        foreach ($rootElement->getElementsByTagName($tagName) as $element) {
            $array[] = [
                'id'           => $element->getAttribute('id'),
                'name'         => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                'requirements' => $this->readRequirements($element)
            ];
        }
        return $array;
    }

    public function readRequirements(\DOMElement $rootElement): array
    {
        return [];
    }
}
