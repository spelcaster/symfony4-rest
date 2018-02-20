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

        $response = $this->client->post('/api/programmers', [
            'body' => json_encode($data)
        ]);

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

        $response = $this->client->get($uri);

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

        $response = $this->client->get($uri);

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

        $response = $this->client->put($uri, [
            'body' => json_encode($expectedData)
        ]);

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

        $response = $this->client->put($uri, [
            'body' => json_encode($expectedData)
        ]);

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

        $response = $this->client->delete($uri);

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

        $response = $this->client->patch($uri, [
            'body' => json_encode($expectedData)
        ]);

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
}
