<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Simula un server para el guzzle client
 */
class FakeServer implements EventSubscriberInterface
{
    private $configuration;
    private $resourceLoader;
    private $receivedRequests = array();
    private $receivedRequestsHashes = array();
    private $defaultStatusCode;
    private $defaultResponseBody;
    private $defaultResponseHeaders;

    /**
     * Constructor
     *
     * @param $configuration Objeto que guarda la configuración de los mapeos
     * @param $resourceLoader Objeto que carga los recursos
     */
    public function __construct(ConfigurationInterface $configuration,
        ResourceLoaderInterface $resourceLoader, 
        $defaultStatusCode = 200, 
        $defaultResponseBody = '', 
        array $defaultResponseHeaders = array())
    {
        $this->configuration = $configuration;
        $this->resourceLoader = $resourceLoader;
        $this->defaultStatusCode = $defaultStatusCode;
        $this->defaultResponseBody = $defaultResponseBody;
        $this->defaultResponseHeaders = $defaultResponseHeaders;
    }

    /**
     * Devuelve los eventos a los que se va a subscribir el objeto
     */
    public static function getSubscribedEvents()
    {
        return array('request.before_send' => array('onRequestBeforeSend', -999));
    }

    /**
     * Devuelve las peticiones realizadas por el guzzle client que tienen mapeo en la configuración
     *
     * @return array
     */
    public function getReceivedRequests()
    {
        return $this->receivedRequests;
    }

    /**
     * Elimina el registro de las peticiones recibidas
     */
    public function clearReceivedRequests()
    {
        $this->receivedRequests = array();
        $this->receivedRequestsHashes = array();   
    }

    /**
     * Metodo que se subscribe al evento request.before_send del guzzle client. Si la URL, metodo y parametros corresponde con algún mapeo de la configuración se establece la respuesta al request
     */
    public function onRequestBeforeSend(Event $event)
    {
        $request = $event['request'];
        $resourceMap = $this->findResourMapForRequest($request);

        try{
            $request->setResponse($this->buildResponse($resourceMap['response']));
        }catch(\Exception $e){
            throw $e;
        }finally{
            $this->enqueReceivedRequest($request);
        }
    }

    /**
     * Busca en los mappings el mapping que mejor se ajusta a la request. Si no encuenta ninguno, lanza una excepción
     *
     * @param $request
     */
    private function findResourMapForRequest($request)
    {
        $bestMatchingResource = null;

        foreach ($this->configuration->getResourceMappings() as $resource){
            if($request->getUrl() == $resource['url'] && $request->getMethod() == $resource['method']){
                //si coincide la url y el metodo, y la configuración establece que se requieren una serie de parametos, miramos que todos esten presentes
                if(!isset($resource['params']) || 
                    $this->requestContainsAllPostFields($request, $resource['params'])){
                    $bestMatchingResource = $resource;
                }
            }
        }

        if($bestMatchingResource == null){//ldd($request->getUrl());
            return array(
                'url' => $request->getUrl(),
                'method' => $request->getMethod(),
                'response' => array(
                    'status' => $this->defaultStatusCode,
                    'body' => $this->defaultResponseBody
                    )
                );
        }

        $bestMatchingResource['response']['body'] = $this->resourceLoader->loadResource($bestMatchingResource['response']['resource']);

        return $bestMatchingResource;
    }

    /**
     * Comprueba que una request tenga todos los parametros (enviados por POST, PUT o PATCH) que se requieren y tengan los mismos valores
     *
     * @return boolean
     */
    private function requestContainsAllPostFields($request, $requiredFields)
    {
        $sentFields = $this->extractPostFields($request);
        return $this->sentFieldsContainAllRequiredPostFields($sentFields, $requiredFields);
    }

    private function sentFieldsContainAllRequiredPostFields($sentFields, $requiredFields)
    {
        foreach ($requiredFields as $key => $value) {
            if(!isset($sentFields[$key])){
                return false;
            }else if(!is_array($value) && $sentFields[$key] != $value){
                return false;
            }else if(isset($sentFields[$key]) && is_array($value) && !$this->sentFieldsContainAllRequiredPostFields($sentFields[$key], $value)){
                return false;
            }
        }

        return true;
    }

    /**
     * Extrae los campos enviados por POST, PUT o PATCH
     *
     * @return array
     */
    private function extractPostFields($request)
    {
        if(($body = $request->getBody())){
            $content = $body->read($body->getContentLength());
            $body->seek(0);

            try{
                return json_decode($content, true);
            }catch(\Exception $e){
                return array();
            }
        }

        return $request->getPostFields();
    }

    /**
     * Crea un response a partir de la configuración estableciendo el body y status code
     *
     * @param $responseMap array con body y status code
     */
    private function buildResponse($responseMap)
    {
        $response = new \Guzzle\Http\Message\Response($responseMap['status']);
        foreach ($this->defaultResponseHeaders as $header => $value) {
            $response->setHeader($header, $value);
        }

        $response->setBody($responseMap['body']);

        return $response;
    }

    /**
     * Encola un request a las request recibidas si aun no fue encolada
     *
     * @param $request Request a añadir
     */
    private function enqueReceivedRequest($request)
    {
        $requestHash = serialize(array($request->getUrl(), $request->getMethod()));

        if(!in_array($requestHash, $this->receivedRequestsHashes)){
            $this->receivedRequestsHashes[] = $requestHash;
            $this->receivedRequests[] = $request;
        }
    }
}