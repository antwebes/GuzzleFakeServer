<?php
namespace Ant\Bundle\GuzzleFakeServer\Behat\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Gherkin\Node\TableNode;

use Symfony\Component\HttpKernel\KernelInterface;
use Behat\Symfony2Extension\Context\KernelAwareInterface;

use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\FakeServer;
use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\FileResourceLoader;
use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\ArrayConfiguration;


class Context extends  MinkContext implements KernelAwareInterface
{
    protected $fakeServer = null;
    protected $fakeServerMappings;
    protected $rememberMeToken = null;

    /**
     * Sets HttpKernel instance.
     * This method will be automatically called by Symfony2Extension ContextInitializer.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     */
    public function initScenario()
    {
        $this->doInitFakeServer();
    }

    protected function doInitFakeServer()
    {
        if($this->fakeServer != null){
            return;
        }

        $apiEndpoint = $this->kernel->getContainer()->getParameter('api_endpoint');
        $resourcesBasePath = $this->kernel->locateResource('@'.$this->kernel->getContainer()->getParameter('guzzle_fake_server_base_fixtures_path'));

        $this->fakeServerMappings = new ArrayConfiguration($apiEndpoint);
        $loader = new FileResourceLoader($resourcesBasePath);

        //since the app needs an access token we configure the mapping of the API URL to obtain the access token
        $this->fakeServerMappings->addPostResource(
            '/oauth/v2/token',
            'fixtures/login/success_client_login.json',
            200,
            array(
                "grant_type" => "client_credentials",
                "client_id" => "1_social_client_id",
                "client_secret" => "social_client_secret"
            )
        );

        //we configure fakeServer with the default response if no resource is found for a URL
        $this->fakeServer = new FakeServer(
            $this->fakeServerMappings,
            $loader,
            200,
            '""',
            array('Content-Type' => 'application/json'));

        $client = $this->kernel->getContainer()->get('antwebes_client');
        $clientSecure = $this->kernel->getContainer()->get('antwebs_chateasecure.guzzle_client');
        $clientAuth = $this->kernel->getContainer()->get('antwebes_client_auth');

        //subscribe to the clients to intercept the calls
        $client->addSubscriber($this->fakeServer);
        $clientSecure->addSubscriber($this->fakeServer);
        $clientAuth->addSubscriber($this->fakeServer);
    }

    /**
     * @AfterScenario
     */
    public function restore(ScenarioEvent $event)
    {
        $notResolvedRequests = $this->fakeServer->getNotResolvedRequests();

        //if the scenario has failed and there are URLs that could not be mapped, print them
        if($event->getResult() > 0 && count($notResolvedRequests) > 0){
            $this->printDebug('The following urls where not resolved');
            foreach($notResolvedRequests as $notResolvedRequest){
                $this->printDebug(sprintf("    [%s] %s", $notResolvedRequest['METHOD'], $notResolvedRequest['URL']));
                $parameters = $notResolvedRequest['PARAMETERS'];

                if(count($parameters) > 0){
                    $parametersString = $this->buildParametersString($parameters, "        ");
                    $this->printDebug($parametersString);
                }
            }
        }


        $client = $this->kernel->getContainer()->get('antwebes_client');
        $clientSecure = $this->kernel->getContainer()->get('antwebs_chateasecure.guzzle_client');
        $clientAuth = $this->kernel->getContainer()->get('antwebes_client_auth');

        $client->getEventDispatcher()->removeSubscriber($this->fakeServer);
        $clientSecure->getEventDispatcher()->removeSubscriber($this->fakeServer);
        $clientAuth->getEventDispatcher()->removeSubscriber($this->fakeServer);
        try {
            $this->kernel->getContainer()->get('security.context')->setToken(null);
            $this->kernel->getContainer()->get('session')->invalidate();
        } catch (\Exception $e) {
        }

        $this->fakeServer = null;
    }

    /**
     * @Then /^the API url "([^"]*)" should have been called$/
     */
    public function theApiUrlShouldHaveBeenCalled($url)
    {
        $this->thenTheApiUrlShouldHaveBeenCalledUsingTheMethod($url, "GET");
    }

    /**
     * @Then /^the API url "([^"]*)" should have been called using the "([^"]*)" method$/
     */
    public function thenTheApiUrlShouldHaveBeenCalledUsingTheMethod($url, $method)
    {
        $this->theApiUrlShouldHaveBeenCalledUsingTheMethodWithTheFollowingParameters($url, $method, null);
    }

    /**
     * @Given /^the API url "([^"]*)" should have been called using the "([^"]*)" method with the following parameters:$/
     */
    public function theApiUrlShouldHaveBeenCalledUsingTheMethodWithTheFollowingParameters(
        $url,
        $method,
        TableNode $expectedSentParams = null
    ) {
        $url = $this->kernel->getContainer()->getParameter('api_endpoint') . $url;

        $found = false;
        $foundButNotValidParams = null;

        foreach ($this->fakeServer->getReceivedRequests() as $request) {
            if ($request->getUrl() == $url && $request->getMethod() == $method) {
                $found = $this->hasRequuestExpectedSendedParams($request, $expectedSentParams);

                /*
                 * the url was called with the method but not with the right parametes, so we retain the parameters sent
                 * to show them in the exception message
                 */
                if(!$found){
                    $foundButNotValidParams = $this->extractPostFields($request);
                }
            }
        }

        if (!$found) {
            $message = $this->buildAPIUrlNotCalledErrorMessage($foundButNotValidParams, $url, $method);
            throw new ExpectationException($message, $this->getSession());
        }
    }

    private function buildAPIUrlNotCalledErrorMessage($foundButNotValidParams, $url, $method)
    {
        if($foundButNotValidParams === null) {
            return sprintf("Excepted to call the %s API URL with %s method", $url, $method);
        }

        $sentParams = $this->buildParametersString($foundButNotValidParams, "    ");
        return sprintf("The %s API URL with %s method was called but not with the expecteted parameters. The parameters sent were:\n%s", $url, $method, $sentParams);
    }

    private function hasRequuestExpectedSendedParams($request, TableNode $expectedSentParams = null)
    {
        if ($expectedSentParams != null) {
            $sentParameters = $this->extractPostFields($request);
            foreach ($expectedSentParams->getRows() as $row) {
                $sentValue = $this->getValueByPath($sentParameters, $row[0]);
                if ($sentValue != $row[1]) {
                    return false;
                }
            }
        }

        return true;
    }

    private function extractPostFields($request)
    {
        if (($body = $request->getBody())) {
            $content = $body->read($body->getContentLength());
            $body->seek(0);

            try {
                return json_decode($content, true);
            } catch (\Exception $e) {
                return array();
            }
        }

        return $request->getPostFields();
    }

    /**
     * Given an assocciative array and a string with the path separated by "." returns the value of the path if it exists, null in other case
     * Example:
     * $arr = array(
     *     'akey1' => 'avalue1',
     *     'akey2' => array(
     *         'subkey1' => 'asubvalue1',
     *         'subkey2' => 'asubvalue2'
     *     )'
     *     'akey2' => 'avalue3'
     * );
     *
     * and
     *
     * $path = 'akey2.subkey1';
     *
     * $this->getValueByPath($arr, $path) would return 'asubalue1'
     *
     * @param $array
     * @param $path
     * @return mixed
     */
    private function getValueByPath($array, $path)
    {
        $keys = explode(".", $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Builds a string representation of the parameters to be printed in display
     *
     * Example:
     *
     * $arr = array(
     *     'akey1' => 'avalue1',
     *     'akey2' => array(
     *         'subkey1' => 'asubvalue1',
     *         'subkey2' => 'asubvalue2'
     *     )'
     *     'akey2' => 'avalue3'
     * );
     *
     * $this->buildParametersString($arr, "    ") will build the following string:
     * "    akey1: avalue1
     *      akey2:
     *          subkey1: asubvalue1
     *          subkey2: asubvalue2
     *      akey3: avalue3"
     *
     * @param $parameters
     * @param $spaces
     * @return string
     */
    protected function buildParametersString($parameters, $spaces)
    {
        $parts = array();

        foreach($parameters as $parameterKey => $parameterValue){
            if(is_array($parameterValue)){
                $parts[] = sprintf("%s%s:\n%s", $spaces, $parameterKey, $this->buildParametersString($parameterValue, "    ".$spaces));
            }else{
                $parts[] = sprintf("%s%s: %s", $spaces, $parameterKey, $parameterValue);
            }
        }

        return implode("\n", $parts);
    }
}