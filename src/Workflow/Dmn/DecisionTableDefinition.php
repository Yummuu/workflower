<?php

namespace Yummuu\Workflower\Workflow\Dmn;

/**
 * 决策表
 */
class DecisionTableDefinition
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

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
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
}
