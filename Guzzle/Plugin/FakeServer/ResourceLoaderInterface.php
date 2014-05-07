<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

/**
 * Interfaz para cargar recursos, es decir, el contenido del body de la respuesta
 */
interface ResourceLoaderInterface
{
    /**
     * Carga un recurso
     *
     * @param $resource ruta al recurso a cargar
     *
     * @return string
     */
    public function loadResource($resource);
}