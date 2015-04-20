<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Simulates a server for the guzzle cliente
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
     * @param $configuration Object that stores the configuration of the mappings
     * @param $resourceLoader Object that loads the de resources
     * @param $defaultStatusCode the default status code to return for found resources
     * @param $defaultResponseHeaders the default header to put in the response
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
     * Returns the events the object will subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array('request.before_send' => array('onRequestBeforeSend', -999));
    }

    /**
     * Returns all requests made to guzzle that have a mapping
     *
     * @return array
     */
    public function getReceivedRequests()
    {
        return $this->receivedRequests;
    }

    /**
     * Clears all received requests
     */
    public function clearReceivedRequests()
    {
        $this->receivedRequests = array();
        $this->receivedRequestsHashes = array();   
    }

    /**
     * Method that subscribes to the request.before_send event of the guzzle client. If a URL, method and parametes matches a given mappinga of the configuration a response is setted to the request
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
     * Searches through all mappings for the mapping that best matches the request. If no one is found, an exception is thrown
     *
     * @param $request
     */
    private function findResourMapForRequest($request)
    {
        $bestMatchingResource = null;

        foreach ($this->configuration->getResourceMappings() as $resource){
            if($request->getUrl() == $resource['url'] && $request->getMethod() == $resource['method']){
                //now tha url and method matches, we verify that all requested parameters are sent
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
     * Verifies that a request has all parameters that are required and have the same value
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
     * Extracts all fields sent by POST, PUT or PATCH
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
     * Creates a respons from the given configuration establishing the boyd and status code
     *
     * Example:
     *
     * $this->buildResponse(array(
     *     'status' => 200,
     *     'body' => 'a body'
     * ))
     *
     * @param $responseMap array with body and status code
     *
     * @return Guzzle\Http\Message\Response
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
     * Enqueuess a request to all received requests
     *
     * @param $request Request to enqueue
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