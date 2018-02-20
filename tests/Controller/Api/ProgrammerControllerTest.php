<?php

namespace App\Tests\Controller\Api;

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
        $expectedUri = "/api/programmers/CoolGuy";
        $expectedStatus = 201;

        $data = [
            'nickname' => $expectedNickname,
            'avatarNumber' => rand(1,6),
            'tagLine' => 'Yay, I\'m a tester'
        ];

        $response = $this->request(
            '/api/programmers',
            ['body' => json_encode($data)],
            'POST'
        );

        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertEquals($expectedUri, $response->getHeader('Location')[0]);
        $this->assertEquals($expectedNickname, $data['nickname']);
    }

    public function test_GivenKnownProgrammer_WhenRequest_ShouldShowProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedStatus = 200;
        $expectedProps = [
            'nickname', 'avatarNumber', 'powerLevel', 'tagLine'
        ];

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);


        $response = $this->request($uri);

        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->asserter()
            ->assertResponsePropertiesExist($response, $expectedProps);

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'nickname', $expectedNickname
            );
    }

    public function test_GivenListOfProgrammers_WhenRequest_ShouldShowProgrammers()
    {
        $expectedCount = 2;
        $expectedStatus = 200;
        $expectedProp = 'programmers';
        $expectedNickname = 'Programmer2';

        $uri = "/api/programmers";

        $this->createProgrammer([
            'nickname' => 'Programmer1',
            'avatarNumber' => 1,
            'tagLine' => 'aloha2'
        ]);

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 2,
            'tagLine' => 'aloha2'
        ]);

        $response = $this->request($uri);

        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $this->asserter()
            ->assertResponsePropertyIsArray($response, $expectedProp);

        $this->asserter()
            ->assertResponsePropertyCount($response, $expectedProp, $expectedCount);

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, $expectedProp . '[1].nickname', $expectedNickname
            );
    }

    public function test_GivenExistentProgrammer_WhenUpdating_ShouldChangeExistentProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedStatus = 200;
        $expectedProp = 'avatarNumber';
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
            ['body' => json_encode($expectedData)],
            'PUT'
        );

        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, $expectedProp, $expectedData[$expectedProp]
            );

    }

    public function test_GivenExistentProgrammer_WhenUpdatingNickname_ShouldNotChangeNickname()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedStatus = 200;
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
            ['body' => json_encode($expectedData)],
            'PUT'
        );

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
        $expectedStatus = 204;

        $uri = "/api/programmers/$expectedNickname";

        $this->createProgrammer([
            'nickname' => $expectedNickname,
            'avatarNumber' => 1,
            'tagLine' => 'aloha'
        ]);

        $response = $this->request($uri, [], 'DELETE');

        $this->assertEquals($expectedStatus, $response->getStatusCode());
    }

    public function test_GivenExistentProgrammer_WhenPatching_ShouldChangeExistentProgrammer()
    {
        $expectedNickname = uniqid("Shurelous");
        $expectedStatus = 200;
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
            ['body' => json_encode($expectedData)],
            'PATCH'
        );

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
        $expectedStatus = 400;

        $data = [
            'avatarNumber' => rand(1,6),
            'tagLine' => 'aloha'
        ];

        $expectedProps = ['type', 'title', 'errors'];

        $response = $this->request(
            '/api/programmers',
            ['body' => json_encode($data)],
            'POST'
        );

        $this->assertEquals($expectedStatus, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);

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

        $expectedProps = ['type', 'title', 'errors'];

        $response = $this->request(
            '/api/programmers',
            ['body' => $invalidJson],
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
        $response = $this->request('/api/programmers/noop');

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
}
