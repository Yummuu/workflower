<?php

namespace Yummuu\Workflower\Workflow\Dmn;

class DRDDefinition
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
     * @var array
     */
    private $decisions  = [];

    /**
     * 业务知识模型
     * @var array
     */
    private $businessKnowledgeModel = [];

    /**
     * input输入
     * @var array
     */
    private $inputData = [];

    /**
     * 知识依赖数据
     *
     * @var array
     */
    private $knowledgeSource = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }
}
