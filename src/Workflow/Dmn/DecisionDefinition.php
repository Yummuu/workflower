<?php

namespace Yummuu\Workflower\Workflow\Dmn;

use Exception;

class DecisionDefinition extends Definition
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $name = null;
    /**
     * @var array
     */
    private $sequenceFlows = [];
    /**
     * @var DecisionTableDefinition
     */
    private $decisionTable;
    /**
     * @var array
     */
    private $requirements = [];
    /**
     * @var string
     */
    private $literalExpression = '';

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * 从外部直接加载数据时，将数据实例化
     */
    public function initDecisionClass()
    {
        if ($this->decisionTable) {
            if ($this->decisionTable instanceof DecisionTableDefinition) {
                return true;
            }
            if (is_array($this->decisionTable)) {
                $this->decisionTable = new DecisionTableDefinition($this->decisionTable);
            } else {
                throw new Exception('unkown type of decisionTable');
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

    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function toArray(): array
    {
        $decisionTable = [];
        if ($this->decisionTable instanceof DecisionTableDefinition) {
            $decisionTable = $this->decisionTable->toArray();
        }
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'sequenceFlows'     => $this->sequenceFlows,
            'decisionTable'     => $decisionTable,
            'requirements'      => $this->requirements,
            'literalExpression' => $this->literalExpression
        ];
    }

    public function getXmlNode(\DOMDocument $document): \DOMElement
    {
        $node   = $document->createElement("decision");
        $node->setAttribute("id", $this->id);
        $node->setAttribute("name", $this->name ? $this->name : "Decision_1");


        if ($this->decisionTable instanceof DecisionTableDefinition) {
            $node->appendChild($this->decisionTable->getXmlNode($document));
        }
        if ($this->literalExpression) {
            $child = $document->createElement("literalExpression");
            $child->setAttribute("expressionLanguage", "javascript");
            $textNode = $document->createElement("text");
            $textNode->textContent = $this->literalExpression;
            $child->appendChild($textNode);
            $node->appendChild($child);
        }
        return $node;
    }
}
