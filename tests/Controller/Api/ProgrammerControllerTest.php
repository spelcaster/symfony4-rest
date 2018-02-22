<?php

namespace App\Tests\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use App\Test\ApiTestCase;

class ProgrammerControllerTest extends ApiTestCase
{
    public function setup()
    {
        parent::setup();

        $this->createUser('user');
    }

    public function test_GivenValidUser_WhenCreating_ShouldNotBreak()
    {
        $expectedNickname = "CoolGuy";
        $data = [
            'nickname' => $expectedNickname,
            'avatarNumber' => rand(1,6),
            'tagLine' => 'Yay, I\'m a tester'
        ];

        $response = $this->request(
            '/api/programmers',
            [
                'body' => json_encode($data),
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'POST'
        );

        $expectedStatus = 201;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedUri = "/api/programmers/CoolGuy";
        $this->assertEquals($expectedUri, $response->getHeader('Location')[0]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $this->assertEquals($expectedNickname, $data['nickname']);
    }

    public function test_GivenKnownProgrammer_WhenRequest_ShouldShowProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request($uri, [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedProps = [
            'nickname', 'avatarNumber', 'powerLevel', 'tagLine'
        ];
        $this->asserter()
            ->assertResponsePropertiesExist($response, $expectedProps);

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'nickname', $expectedNickname
            );

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, '_links.self', $uri
            );
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function test_GivenExistentProgrammer_WhenDeepRequest_ShouldShowDeepProp()
    {
        $expectedNickname = uniqid("Shurelous");
        $uri = "/api/programmers/$expectedNickname?deep=1";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request($uri, [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedProp = 'user';
        $this->asserter()
            ->assertResponsePropertyExists($response, $expectedProp);
    }

    public function test_GivenListOfProgrammers_WhenRequest_ShouldShowProgrammers()
    {
        $uri = "/api/programmers";

        $this->createProgrammer([
            'nickname' => 'Programmer1',
            'avatarNumber' => 1,
            'tagLine' => 'aloha2'
        ]);

        $expectedNickname = 'Programmer2';
        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 2,
            'tagLine' => 'aloha2'
        ]);

        $response = $this->request($uri, [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedProp = 'items';
        $this->asserter()
            ->assertResponsePropertyIsArray($response, $expectedProp);

        $expectedCount = 2;
        $this->asserter()
            ->assertResponsePropertyCount($response, $expectedProp, $expectedCount);

        $expectedProp = 'items[1].nickname';
        $this->asserter()
            ->assertResponsePropertyEquals($response, $expectedProp, $expectedNickname);
    }

    public function test_GivenListOfProgrammers_WhenRequest_ShouldShowProgrammersWithPagination()
    {
        $this->createProgrammer([
            'nickname' => "willnotmatch",
            'avatarNumber' => 3,
        ]);

        for ($i = 0; $i < 25; $i++) {
            $this->createProgrammer([
                'nickname' => "Programmer$i",
                'avatarNumber' => rand(1, 6),
                'tagLine' => "aloha $i"
            ]);
        }

        $uri = "/api/programmers?filter=programmer";

        $response = $this->request($uri, [
            'headers' => $this->getAuthorizedHeaders('user')
        ]);

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedNickname = 'Programmer5';
        $expectedProp = 'items[5].nickname';
        $this->asserter()
            ->assertResponsePropertyEquals($response, $expectedProp, $expectedNickname);

        $expectedCount = 10;
        $this->asserter()
            ->assertResponsePropertyEquals($response, 'count', $expectedCount);

        $expectedTotal = 25;
        $this->asserter()
            ->assertResponsePropertyEquals($response, 'total', $expectedTotal);

        $this->asserter()
            ->assertResponsePropertyExists($response, '_links.next');

        $response = $this->request(
            $this->asserter()->readResponseProperty($response, '_links.next'),
            [
                'headers' => $this->getAuthorizedHeaders('user')
            ]
        );

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedNickname = 'Programmer15';
        $expectedProp = 'items[5].nickname';
        $this->asserter()
            ->assertResponsePropertyEquals($response, $expectedProp, $expectedNickname);

        $expectedCount = 10;
        $this->asserter()
            ->assertResponsePropertyEquals($response, 'count', $expectedCount);

        $this->asserter()
            ->assertResponsePropertyExists($response, '_links.last');

        $response = $this->request(
            $this->asserter()->readResponseProperty($response, '_links.last'),
            [
                'headers' => $this->getAuthorizedHeaders('user')
            ]
        );

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedNickname = 'Programmer24';
        $expectedProp = 'items[4].nickname';
        $this->asserter()
            ->assertResponsePropertyEquals($response, $expectedProp, $expectedNickname);

        $expectedProp = 'items[5].nickname';
        $this->asserter()
            ->assertResponsePropertyDoesNotExist($response, $expectedProp);

        $expectedCount = 5;
        $this->asserter()
            ->assertResponsePropertyEquals($response, 'count', $expectedCount);

        $this->asserter()
            ->assertResponsePropertyExists($response, '_links.first');
    }

    public function test_GivenExistentProgrammer_WhenUpdating_ShouldChangeExistentProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedData = [
            'nickname' => $expectedNickname,
            'avatarNumber' => 6,
            'tagLine' => 'worked'
        ];

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request(
            $uri,
            [
                'body' => json_encode($expectedData),
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'PUT'
        );

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedProp = 'avatarNumber';
        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, $expectedProp, $expectedData[$expectedProp]
            );
    }

    public function test_GivenExistentProgrammer_WhenUpdatingNickname_ShouldNotChangeNickname()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedData = [
            'nickname' => 'oops',
            'avatarNumber' => 2
        ];

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request(
            $uri,
            [
                'body' => json_encode($expectedData),
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'PUT'
        );

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $this->asserter()
            ->assertResponsePropertyNotEquals(
                $response, 'nickname', $expectedData['nickname']
            );

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'avatarNumber', $expectedData['avatarNumber']
            );
    }

    public function test_GivenExistentProgrammer_WhenDelete_ShouldDeleteProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request(
            $uri,
            ['headers' => $this->getAuthorizedHeaders('user')],
            'DELETE'
        );

        $expectedStatus = 204;
        $this->assertEquals($expectedStatus, $response->getStatusCode());
    }

    public function test_GivenExistentProgrammer_WhenPatching_ShouldChangeExistentProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedData = [
            'tagLine' => 'worked'
        ];

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request(
            $uri,
            [
                'body' => json_encode($expectedData),
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'PATCH'
        );

        $expectedStatus = 200;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'tagLine', $expectedData['tagLine']
            );

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'avatarNumber', 1
            );
    }

    public function test_GivenInvalidProgrammer_WhenCreating_ShouldShowErrors()
    {
        $data = [
            'avatarNumber' => rand(1,6),
            'tagLine' => 'aloha'
        ];

        $response = $this->request(
            '/api/programmers',
            [
                'body' => json_encode($data),
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'POST'
        );

        $expectedStatus = 400;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedProps = ['type', 'title', 'errors'];
        $this->asserter()
            ->assertResponsePropertiesExist($response, $expectedProps);

        $this->asserter()
            ->assertResponsePropertyExists($response, 'errors.nickname');

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response,
                'errors.nickname[0]',
                'Please enter a clever nickname'
            );

        $this->asserter()
            ->assertResponsePropertyDoesNotExist(
                $response, 'errors.avatarNumber'
            );

        $expectedContent = 'application/problem+json';
        $this->assertEquals(
            $expectedContent, $response->getHeader('Content-Type')[0]
        );
    }

    public function test_GivenInvalidJson_WhenAnyRequest_ShouldFailWith422()
    {
        $invalidJson = <<<EOF
{
    "nickname": "asdadasd,
    "avatarNumber": 1,
    "tagLine": "aloha"
}
EOF;

        $response = $this->request(
            '/api/programmers',
            [
                'body' => $invalidJson,
                'headers' => $this->getAuthorizedHeaders('user')
            ],
            'POST'
        );

        $expectedStatus = 422;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedContent = 'application/problem+json';
        $this->assertEquals(
            $expectedContent, $response->getHeader('Content-Type')[0]
        );

        $this->asserter()
            ->assertResponsePropertyContains($response, 'type', 'invalid_body_format');
    }

    public function test_GivenInexistentProgrammer_WhenShow_ShouldFailWith404()
    {
        $response = $this->request(
            '/api/programmers/noop',
            [
                'headers' => $this->getAuthorizedHeaders('user')
            ]
        );

        $expectedStatus = 404;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedContent = 'application/problem+json';
        $this->assertEquals(
            $expectedContent, $response->getHeader('Content-Type')[0]
        );

        $this->asserter()
            ->assertResponsePropertyEquals($response, 'type', 'about:blank');

        $this->asserter()
            ->assertResponsePropertyEquals($response, 'title', 'Not Found');

        $this->asserter()
            ->assertResponsePropertyEquals($response, 'detail', 'No programmer found with username noop');
    }

    public function test_GivenNewProgrammer_WhenUnauthorized_ShouldReturn401()
    {
        $response = $this->request(
            '/api/programmers',
            ['body' => '{}'],
            'POST'
        );

        $expectedStatus = 401;
        $this->assertEquals($expectedStatus, $response->getStatusCode());
    }

    public function test_GivenInvalidToken_WhenAnyRequest_ShouldFailWith401()
    {
        $response = $this->request(
            '/api/programmers/noop',
            [
                'headers' => [
                    'Authorization' => 'shurelous'
                ]
            ]
        );

        $expectedStatus = Response::HTTP_UNAUTHORIZED;
        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $expectedContent = 'application/problem+json';
        $this->assertEquals(
            $expectedContent, $response->getHeader('Content-Type')[0]
        );

        $this->asserter()
            ->assertResponsePropertyEquals($response, 'type', 'about:blank');

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'title', Response::$statusTexts[$expectedStatus]
            );
    }
}
