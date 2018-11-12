<?php

namespace Recca0120\Olami\Tests;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Recca0120\Olami\Client;

class ClientTest extends TestCase
{
    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        if (isset($_ENV['APP_KEY']) === false || isset($_ENV['APP_SECRET']) === false) {
            $this->markTestSkipped('APP_KEY or APP_SECRET not exists');
        }
    }

    /** @test */
    public function test_query_nli()
    {
        $httpClient = HttpClientDiscovery::find();
        $messageFactory = MessageFactoryDiscovery::find();

        $client = new Client($_ENV['APP_KEY'], $_ENV['APP_SECRET'], $httpClient, $messageFactory);

        $params = [
            'api' => 'nli',
            'rq' => [
                'data' => [
                    'input_type' => 1,
                    'text' => '台中天氣',
                ],
                'data_type' => 'stt',
            ],
        ];

        $this->assertArraySubset(['status' => 'ok'], $client->query($params));
    }

    /** @test */
    public function test_query_asr()
    {
        $httpClient = HttpClientDiscovery::find();
        $messageFactory = MessageFactoryDiscovery::find();

        $client = new Client($_ENV['APP_KEY'], $_ENV['APP_SECRET'], $httpClient, $messageFactory);

        $params = [
            'api' => 'asr',
            'sound' => __DIR__.'/sample.wav',
        ];

        $this->assertArraySubset([
            'data' => [
                'asr' => [
                    'final' => true,
                ],
            ],
            'status' => 'ok',
        ], $client->query($params));
    }
}
