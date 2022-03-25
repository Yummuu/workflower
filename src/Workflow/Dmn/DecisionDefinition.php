<?php

namespace Yummuu\Workflower\Workflow\Dmn;

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
}
