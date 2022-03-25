<?php

namespace Yummuu\Workflower\Workflow\Dmn;

/**
 * 定义
 */
abstract class Definition
{

    /**
     * {@inheritdoc}
     */
    abstract function serialize();

    /**
     * 反序列化
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
    /**
     * 转为json数据
     *
     * @return string
     */
    abstract public function toJson(): string;

    /**
     * 转为数组
     *
     * @return array
     */
    abstract public function toArray(): array;
}
