<?php

namespace Yummuu\Workflower\Workflow\Operation;

use Exception;
use Yummuu\Workflower\Workflow\Dmn\DRDDefinition;

class DRDRunner implements DRDRunnerInterface
{
    /**
     * @param string $source
     * @return DRDDefinition
     */
    public function readFromSource(string $source): DRDDefinition
    {
        if (!$source) {
            throw new Exception('source is null');
        }
        $class = unserialize($source);
        if ($class instanceof DRDDefinition) {
            return $class;
        } else {
            $arr = json_decode($source, true);
            if (!is_array($arr)) {
                throw new Exception('source is not array json');
            }
            return $this->readFromArray($arr);
        }
    }

    /**
     * @param array $data
     * @return DRDDefinition
     */
    public function readFromArray(array $data): DRDDefinition
    {
        $definition = new DRDDefinition($data);
        $definition->initDecisionClass();
        return $definition;
    }

    /**
     * @param DRDDefinition $data
     * @param array $sourceData
     */
    public function run(DRDDefinition $data, array $sourceData)
    {
    }
}
