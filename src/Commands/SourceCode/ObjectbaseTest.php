<?php declare(strict_types=1);

namespace Tests\integration;

class ObjectbaseTest extends BaseTestCase
{
    private static $id;

    public function testCreateObjectbase()
    {
        $response = $this->runApp(
            'POST',
            '/objectbase',
            [
                #postParams
            ]
        );

        $result = (string) $response->getBody();

        self::$id = json_decode($result)->id;

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbases()
    {
        $response = $this->runApp('GET', '/objectbase');

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbase()
    {
        $response = $this->runApp('GET', '/objectbase/' . self::$id);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbaseNotFound()
    {
        $response = $this->runApp('GET', '/objectbase/123456789');

        $result = (string) $response->getBody();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('error', $result);
    }

    public function testUpdateObjectbase()
    {
        $response = $this->runApp('PUT', '/objectbase/' . self::$id, ['' => '']);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testDeleteObjectbase()
    {
        $response = $this->runApp('DELETE', '/objectbase/' . self::$id);

        $result = (string) $response->getBody();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertStringNotContainsString('error', $result);
    }
}
