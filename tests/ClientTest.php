<?php

namespace Recca0120\Olami\Tests;

use Recca0120\Olami\Client;
use PHPUnit\Framework\TestCase;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

class ClientTest extends TestCase
{
    private $client;

    protected function setUp()
    {
        parent::setUp();

        if (isset($_ENV['APP_KEY']) === false || isset($_ENV['APP_SECRET']) === false) {
            $this->markTestSkipped('APP_KEY or APP_SECRET not exists');
        }

        $httpClient = HttpClientDiscovery::find();
        $messageFactory = MessageFactoryDiscovery::find();

        $this->client = new Client($_ENV['APP_KEY'], $_ENV['APP_SECRET'], $httpClient, $messageFactory);
    }

    public function test_query_nli()
    {
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

        $this->assertArraySubset(['status' => 'ok'], $this->client->query($params));
    }

    public function test_query_asr()
    {
        copy(__DIR__.'/sample2-441k.wav', __DIR__.'/sample2.wav');

        $params = [
            'api' => 'asr',
            'sound' => __DIR__.'/sample2.wav',
        ];

        $this->assertArraySubset([
            'data' => [
                'asr' => [
                    'final' => true,
                ],
            ],
            'status' => 'ok',
        ], $this->client->query($params));
    }
}
