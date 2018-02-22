<?php

namespace App\Tests\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use App\Test\ApiTestCase;

class TokenControllerTest extends ApiTestCase
{
    public function test_GivenTokenRequest_WhenValidUser_ThenShouldReturnValidToken()
    {
        $this->createUser('user', '123456');

        $response = $this->request(
            '/api/tokens',
            ['auth' => ['user', '123456']],
            'POST'
        );

        $expectedStatus = 201;
        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyExists($response, 'token');
    }

    public function test_GivenTokenRequest_WhenInvalidUser_ThenShouldNotReturnToken()
    {
        $this->createUser('user', '123456');

        $response = $this->request(
            '/api/tokens',
            ['auth' => ['user', '12345']],
            'POST'
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

        $this->asserter()
            ->assertResponsePropertyEquals(
                $response, 'detail', 'Invalid credentials.'
            );
    }
}
