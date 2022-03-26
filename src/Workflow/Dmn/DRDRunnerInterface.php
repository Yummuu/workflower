<?php

namespace Yummuu\Workflower\Workflow\Operation;

use Yummuu\Workflower\Workflow\Dmn\DRDDefinition;

interface DRDRunnerInterface
{
    /**
     * @param string $source
     * @return DRDDefinition
     */
    public function readFromSource(string $source): DRDDefinition;

    /**
     *
     * @param array $data
     * @return DRDDefinition
     */
    public function readFromArray(array $data):DRDDefinition;

    /**
     *
     * @param DRDDefinition $data
     * @param array $sourceData
     */
    public function run(DRDDefinition $data, array $sourceData);

}
