<?php

namespace Yummuu\Workflower\Workflow\Dmn;

use Exception;

class DRDDefinition extends Definition
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
     * 决策表
     * @var DecisionDefinition[]
     */
    private $decisions  = [];

    /**
     * input输入
     * @var array
     */
    private $inputData = [];

    /**
     * 业务知识模型
     * @var array
     */
    private $businessKnowledgeModel = [];

    /**
     * 知识依赖数据
     *
     * @var array
     */
    private $knowledgeSource = [];

    /**
     * 外部输入
     *
     * @var array
     */
    public $sourceData = [];

    /**
     * 执行结果-分决策表
     *
     * @var array
     */
    public $outData = [];

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
     * @return string
     */
    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'businessKnowledgeModel' => $this->businessKnowledgeModel,
            'knowledgeSource'        => $this->knowledgeSource,
            'inputData'              => $this->inputData,
            'decisions'              =>  array_map(function ($item) {
                return $item->toArray();
            }, $this->decisions)
        ];
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     *
     * @param array $data
     */
    public function setInputData(array $data)
    {
        if (!is_array($data)) {
            throw new Exception('source data is not array');
        }
        $this->sourceData = $data;
        if ($this->inputData && is_array($this->inputData)) {
            foreach ($this->inputData as &$value) {
                $key  = trim($value['name']);
                if (isset($this->sourceData[$key])) {
                    $value['data'] = $this->sourceData[$key];
                }
            }
        }
    }
}
