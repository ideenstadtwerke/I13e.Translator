<?php

namespace I13e\Translator\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;

class NodeTypeConfigurationService
{
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @param string $package
     * @return array
     * @throws InvalidConfigurationTypeException
     */
    public function getNodeTypes(string $package): array
    {
        $nodeTypes = [];
        $nodeTypeConfiguration = $this->configurationManager->getConfiguration('NodeTypes');

        foreach ($nodeTypeConfiguration as $nodeType => $nodeTypeConfig) {
            if (str_starts_with($nodeType, $package)) {
                $nodeTypes[$nodeType] = $nodeTypeConfig;
            }
        }

        return $nodeTypes;
    }

    /**
     * @param string $nodeTypeName
     * @return array
     * @throws InvalidConfigurationTypeException
     */
    public function getNodeType(string $nodeTypeName): array
    {
        $allNodeTypes = $this->configurationManager->getConfiguration('NodeTypes');

        return $allNodeTypes[$nodeTypeName] ? [$nodeTypeName => $allNodeTypes[$nodeTypeName]] : [];
    }
}
