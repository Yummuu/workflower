<?php

declare(strict_types=1);

namespace Yummuu\Workflower\Workflow\Dmn;

use Exception;

class DRDDefinition extends Definition
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $name = null;
    /**
     * 决策表
     * @var DecisionDefinition[]
     */
    public $decisions  = [];

    /**
     * input输入
     * @var array
     */
    protected $inputData = [];

    /**
     * 业务知识模型
     * @var array
     */
    protected $businessKnowledgeModel = [];

    /**
     * 知识依赖数据
     *
     * @var array
     */
    protected $knowledgeSource = [];

    /**
     * 外部输入
     *
     * @var array
     */
    protected $sourceData = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
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
     * 从外部直接加载数据时，将数据实例化
     */
    public function initDecisionClass()
    {
        $decisions = [];
        foreach ($this->decisions as $item) {
            if ($item instanceof DecisionDefinition) {
                $decisions[] = $item;
                continue;
            }
            if (is_array($item)) {
                $decision = new DecisionDefinition($item);
                $decision->initDecisionClass();
            } else {
                throw new Exception('unkown type of decision');
            }
        }
        $this->decisions = $decisions;
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

    public function toXml(): string
    {
        $document = new \DOMDocument("1.0", 'UTF-8');
        $definitions = $document->createElement("definitions");
        $definitions->setAttribute("id", $this->id ? $this->id : "DRDDefinition");
        $definitions->setAttribute("name", $this->name ? $this->name : "DRD");
        $definitions->setAttribute("exporter", "Camunda Modeler");
        $definitions->setAttribute("exporterVersion", "4.8.1");
        $definitions->setAttribute("namespace", "http://camunda.org/schema/1.0/dmn");
        $namespace = "http://www.w3.org/2000/xmlns/";
        $definitions->setAttributeNS($namespace, 'xmlns:dmndi', 'https://www.omg.org/spec/DMN/20191111/DMNDI/');
        $definitions->setAttributeNS($namespace, 'xmlns:dc', 'http://www.omg.org/spec/DMN/20180521/DC/');
        $definitions->setAttributeNS($namespace, 'xmlns:camunda', 'http://camunda.org/schema/1.0/dmn');
        $definitions->setAttributeNS($namespace, 'xmlns:di', 'http://www.omg.org/spec/DMN/20180521/DI/');
        $definitions->setAttribute("xmlns", "https://www.omg.org/spec/DMN/20191111/MODEL/");
        foreach ($this->inputData as $value) {
            $node = $document->createElement("inputData");
            $node->setAttribute("id", $value['id']);
            $node->setAttribute("name", $value['name']);
            $definitions->appendChild($node);
        }
        foreach ($this->decisions as $item) {
            if ($item instanceof DecisionDefinition) {
                $definitions->appendChild($item->getXmlNode($document));
            }
        }
        $document->appendChild($definitions);
        return $document->saveXML();
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
