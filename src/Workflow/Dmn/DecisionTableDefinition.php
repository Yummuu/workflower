<?php

namespace Yummuu\Workflower\Workflow\Dmn;

/**
 * 决策表
 */
class DecisionTableDefinition extends Definition
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     * FIRST,ANY,UNIQUE,COLLECT
     */
    private $hitPolicy;
    /**
     * @var array
     * [
     *  'id' => "",
     *  'label' => "",
     *  'inputExpressionId' =>'',
     *  'inputExpressionType' =>'',
     *  'inputExpression' =>'',
     *  'inputValuesId' => '',
     *  'inputValues'  => ''
     * ]
     */
    private $input = [];
    /**
     * @var array
     * [
     *  'id' => "",
     *  'label' => "",
     *  'name' => "",
     *  'typeRef' => "",
     *  'inputValuesId' => "",
     *  'inputValues' => "",
     * ]
     */
    private $output = [];
    /**
     * @var array
     * [
     *  'id' => "",
     *  'description' => "",
     *  'input' => [
     *      "input-id" => "value",
     *      "input-id" => "value",
     *  ],
     *  'output' => [ 
     *      "out-id"  => "value",
     *      "out-id"  => "value",
     *  ]
     * ]
     */
    private $rules = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'hitPolicy' => $this->hitPolicy,
            'input'     => $this->input,
            'output'    => $this->output,
            'rules'     => $this->rules,
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 生成XML时的节点问题
     *
     * @param \DOMDocument $document
     * @return \DOMElement
     */
    public function getXmlNode(\DOMDocument $document): \DOMElement
    {
        $node = $document->createElement("decisionTable");
        $node->setAttribute("id", $this->id);
        $node->setAttribute("hitPolicy", $this->hitPolicy);
        //录入input
        foreach ($this->input as $value) {
            $child = $document->createElement("input");
            $child->setAttribute("id", $value['id']);
            $child->setAttribute("label", $value['label']);

            $inputVariable = isset($value['inputVariable']) ? $value['inputVariable'] : $value['label'];
            $child->setAttributeNS('http://camunda.org/schema/1.0/dmn', 'camunda:inputVariable', $inputVariable);

            $inputChild = $document->createElement("inputExpression");
            $inputChild->setAttribute("id", $value['inputExpressionId']);
            $inputChild->setAttribute("typeRef", $value['inputExpressionType']);
            $inputChild->appendChild($this->getTextNode($document, $value['inputExpression']));
            $child->appendChild($inputChild);

            if (isset($value['inputValuesId'])) {
                $inputValueChild = $document->createElement('inputValues');
                $inputValueChild->setAttribute("id", $value['inputValuesId']);
                $inputValueChild->appendChild($this->getTextNode($document, $value['inputValues']));

                $child->appendChild($inputValueChild);
            }

            $node->appendChild($child);
        }
        //录入output
        foreach ($this->output as $value) {
            $child = $document->createElement("output");
            $child->setAttribute("id", $value['id']);
            $child->setAttribute("label", $value['label']);
            $child->setAttribute("name", $value['name']);
            $child->setAttribute("typeRef", $value['typeRef']);
            if (isset($value['inputValuesId'])) {
                $outChild = $document->createElement('outputValues');
                $outChild->setAttribute("id", $value['inputValuesId']);
                $outChild->appendChild($this->getTextNode($document, $value['inputValues']));
                $child->appendChild($outChild);
            }
            $node->appendChild($child);
        }
        $ruleId = 0;
        //录入规则
        foreach ($this->rules as $value) {
            $rule = $document->createElement("rule");
            $rule->setAttribute("id", $value['id']);
            if (isset($value['description'])) {
                $child = $document->createElement("description");
                $child->textContent = $value['description'];
                $rule->appendChild($child);
            }
            foreach ($value['input'] as $id => $v) {
                $ruleId++;
                $child = $document->createElement("inputEntry");
                $child->setAttribute("id", 'UnaryTests_'.$ruleId);
                $child->appendChild($this->getTextNode($document, $v));
                $rule->appendChild($child);
            }
            foreach ($value['output'] as $id => $v) {
                $ruleId++;
                $child = $document->createElement("outputEntry");
                $child->setAttribute("id", 'LiteralExpression_'.$ruleId);
                $child->appendChild($this->getTextNode($document, $v));
                $rule->appendChild($child);
            }
            $node->appendChild($rule);
        }
        return $node;
    }

    private function getTextNode(\DOMDocument $document, $value): \DOMElement
    {
        $textNode = $document->createElement("text");
        $textNode->textContent = $value? $value:" ";
        return $textNode;
    }
}
