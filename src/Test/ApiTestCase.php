<?php

namespace App\Test;

use Throwable;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use App\Entity\User;
use App\Entity\Programmer;
use App\Entity\Battle;

class ApiTestCase extends KernelTestCase
{
    /**
     * @var GuzzleHttp\Client
     */
    private static $staticClient;

    /**
     * @var Guzzle history container
     */
    private static $history;

    /**
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var ResponseAsserter
     */
    protected $responseAsserter;

    public static function setUpBeforeClass()
    {
        $baseUrl = getenv('TEST_BASE_URL');

        self::$history = [];

        $historyMiddleware = Middleware::history(self::$history);

        $stack = HandlerStack::create();
        $stack->push($historyMiddleware);

        // create client once
        self::$staticClient = new Client([
            'base_uri' => $baseUrl,
            'handler' => $stack,
            'defaults' => [
                'exceptions' => false,

            ]
        ]);

        self::bootKernel();
    }

    public function setUp()
    {
        // reuse client
        $this->client = self::$staticClient;
        $this->purgeDatabase();
    }

    public function tearDown()
    {
        // purposefulle overriding so Symfony's kernel isn't shutdown
    }

    protected function onNotSuccessfulTest(Throwable $e)
    {
        if (!count(self::$history)) {
            throw $e;
        }

        $lastItem = end(self::$history);

        $this->printDebug();
        $this->printDebug(
            '<error>Failure!</error> when making the following Request:'
        );
        $this->printLastRequestUri($lastItem['request']);
        $this->printDebug();

        $this->debugResponse($lastItem['response']);

        throw $e;
    }

    private function purgeDatabase()
    {
        $em = $this->getService('doctrine')->getManager();

        $purger = new ORMPurger($em);
        $purger->purge();
    }

    protected function getService($id)
    {
        return self::$kernel->getContainer()->get($id);
    }

    protected function printLastRequestUri()
    {
        if (!count(self::$history)) {
            $this->printDebug('No request was made');
            return;
        }
        $item = end(self::$history);
        $lastRequest = $item['request'];

        $this->printDebug(sprintf(
            '<comment>%s</comment>: <info>%s</info>',
            $lastRequest->getMethod(),
            $lastRequest->getUri()
        ));
    }

    protected function debugResponse(Response $response)
    {
        $this->printDebug(
            $response->getStatusCode() . ' ' . $response->getReasonPhrase()
        );

        foreach ($response->getHeaders() as $name => $values) {
            $this->printDebug($name . ': ' . implode(', ', $values));
        }

        $body = $response->getBody()->getContents();
        $contentType = $response->getHeader('Content-Type')[0];
        if ($contentType == 'application/json' || strpos($contentType, '+json') !== false) {
            $data = json_decode($body);
            if (!$data) {
                // invalid JSON!
                $this->printDebug($body);
                return;
            }

            // valid JSON, print it pretty
            $this->printDebug(json_encode($data, JSON_PRETTY_PRINT));
            return;
        }

        // the response is HTML - see if we should print all of it or some of it
        $isValidHtml = strpos($body, '</body>') !== false;

        if (!$isValidHtml || !$body) {
            $this->printDebug($body);
            return;
        }

        $this->printDebug();
        $crawler = new Crawler($body);
        $title = $crawler->filter('title')->text();
        $this->printErrorBlock($title);

        /*
         * When using the test environment, the profiler is not active
         * for performance. To help debug, turn it on temporarily in
         * the config_test.yml file:
         *   A) Update framework.profiler.collect to true
         *   B) Update web_profiler.toolbar to true
         */
        $profilerUrl = $response->getHeader('X-Debug-Token-Link')[0];
        if ($profilerUrl) {
            $fullProfilerUrl = $response->getHeader('Host')[0].$profilerUrl;
            $this->printDebug();
            $this->printDebug(sprintf(
                'Profiler URL: <comment>%s</comment>',
                $fullProfilerUrl
            ));
        }

        // an extra line for spacing
        $this->printDebug();
    }

    protected function printDebug($str = '')
    {
        if (!$this->output) {
            $this->output = new ConsoleOutput();
        }

        $this->output->writeln($str);
    }

    protected function printErrorBlock($str)
    {
        if (!$this->formatterHelper) {
            $this->formatterHelper = new FormatterHelper();
        }

        $out = $this->formatterHelper->formatBlock(
            $str, 'bg=blue;fg=white', true
        );

        $this->printDebug($out);
    }

    /**
     * @return User
     */
    protected function createUser($username, $plainPassword = 'foo')
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail("$username@example.com");
        $user->setPlainPassword($plainPassword);
        $user->setEnabled(true);

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * @return Programmer
     */
    protected function createProgrammer(array $data)
    {
        $em = $this->getEntityManager();

        $data = array_merge(
            [
                'powerLevel' => rand(0, 10),
                'user' => $em->getRepository('App:User')->findAny()
            ],
            $data
        );

        $accessor = PropertyAccess::createPropertyAccessor();
        $programmer = new Programmer();

        foreach ($data as $key => $value) {
            $accessor->setValue($programmer, $key, $value);
        }

        $em->persist($programmer);
        $em->flush();

        return $programmer;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine.orm.entity_manager');
    }

    /**
     * @return ResponseAsserter
     */
    protected function asserter()
    {
        if (!$this->responseAsserter) {
            $this->responseAsserter = new ResponseAsserter();
        }

        return $this->responseAsserter;
    }

    /**
     * @return GuzzleHttp\Psr7\Response
     */
    protected function request($uri, array $options = [], $method = 'GET')
    {
        $response = new Response();

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch(ClientException $e) { // 400 errors
            $response = $e->getResponse();
        } catch(ServerException $e) { // 500 errors
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function getAuthorizedHeaders($username, $headers = [])
    {
        $token = $this->getService('lexik_jwt_authentication.encoder')
            ->encode(['username' => $username]);

        $headers['Authorization'] = "Bearer $token";

        return $headers;
    }
}
