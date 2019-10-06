<?php declare(strict_types=1);

namespace Tests\integration;

class ObjectbaseTest extends TestCase
{
    private static $id;

    public function testCreateObjectbase()
    {
        $params = [
                '' => '',
                #postParams
        ];
        $app = $this->getAppInstance();
        $request = $this->createRequest('POST', '/objectbase');
        $request = $request->withParsedBody($params);
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        self::$id = json_decode($result)->id;

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbases()
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/objectbase');
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbase()
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/objectbase/' . self::$id);
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testGetObjectbaseNotFound()
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/objectbase/123456789');
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('error', $result);
    }

    public function testUpdateObjectbase()
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('PUT', '/objectbase/' . self::$id);
        $request = $request->withParsedBody(['' => '']);
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('id', $result);
        $this->assertStringNotContainsString('error', $result);
    }

    public function testDeleteObjectbase()
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('DELETE', '/objectbase/' . self::$id);
        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertStringNotContainsString('error', $result);
    }
}
