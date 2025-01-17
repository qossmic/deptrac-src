<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Layer;

interface LayerProviderInterface
{
    /**
     * @return list<string>
     *
     * @throws CircularReferenceException
     */
    public function getAllowedLayers(string $layerName): array;
}
