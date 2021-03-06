<?php

namespace Recca0120\Olami;

use Exception;
use Carbon\Carbon;
use Http\Client\HttpClient;
use Http\Message\CookieJar;
use Http\Message\MessageFactory;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MultipartStream\MultipartStreamBuilder;

class Client
{
    /**
     * @var string
     */
    private $endpoint = 'https://tw.olami.ai/';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var \Recca0120\Olami\Hasher
     */
    private $hasher;

    /**
     * @var \Http\Client\Common\PluginClient
     */
    private $client;

    /**
     * @var \Http\Message\MessageFactory
     */
    private $messageFactory;

    /**
     * Client constructor.
     *
     * @param $apiKey
     * @param $apiSecret
     * @param HttpClient|null $client
     * @param MessageFactory|null $messageFactory
     */
    public function __construct($apiKey, $apiSecret, HttpClient $client = null, MessageFactory $messageFactory = null)
    {
        $this->apiKey = $apiKey;
        $this->hasher = $apiSecret instanceof Hasher ? $apiSecret : new Hasher($apiSecret);
        $this->client = new PluginClient(
            $client ?: HttpClientDiscovery::find(), [
                new CookiePlugin(new CookieJar()),
            ]
        );

        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * @param $endpoint
     *
     * @return $this
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function query($params)
    {
        $uri = 'cloudservice/api';

        return $this->hasSound($params) === true
            ? $this->queryBySound($params, $uri)
            : $this->queryByText($params, $uri);
    }

    /**
     * @param array $params
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function speech($params)
    {
        $params = array_merge([
            'txt' => '歐拉蜜你好',
            'speed' => 1.1,
        ], $params);

        return $this->sendRequest('tts/create?'.http_build_query($params));
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param null $body
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Http\Client\Exception
     */
    private function sendRequest($uri, $method = 'GET', $headers = [], $body = null)
    {
        $request = $this->messageFactory->createRequest($method, $this->endpoint.$uri, $headers, $body);
        $response = $this->client->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function hasSound($params)
    {
        return empty($params['sound']) === false;
    }

    /**
     * @param array $params
     * @param string $uri
     *
     * @return mixed
     * @throws \Http\Client\Exception
     */
    private function queryBySound($params, $uri)
    {
        $params = $this->prepare($params);
        $builder = new MultipartStreamBuilder();
        foreach ($params as $key => $value) {
            is_file($value) === true
                ? $builder->addResource($key, fopen($value, 'r'), ['filename' => $value])
                : $builder->addResource($key, $value);
        }

        $headers = ['Content-Type' => 'multipart/form-data; boundary="'.$builder->getBoundary().'"'];
        $body = $builder->build();
        $this->sendRequest($uri, 'POST', $headers, $body);

        unset($params['sound']);
        unset($params['timestamp']);

        while (true) {
            sleep(1);

            $response = $this->queryByText(array_merge($params, [
                'stop' => 1,
            ]), $uri);

            $asr = Arr::get($response, 'data.asr');
            if (empty($asr) === true || Arr::get($asr, 'status') >= 2) {
                throw new Exception(Arr::get($asr, 'msg', 'unknown'));
            }

            $nli = Arr::get($response, 'data.nli');
            if (empty($nli) === false || (boolean) Arr::get($asr, 'final') === true) {
                return $response;
            }
        }
    }

    /**
     * @param array $params
     * @param string $uri
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Http\Client\Exception
     */
    private function queryByText($params, $uri)
    {
        $params = $this->prepare($params);
        $response = $this->sendRequest($uri.'?'.http_build_query($params));
        $status = Arr::get($response, 'status');

        if ($status !== 'ok') {
            throw new Exception(Arr::get($response, 'msg'), Arr::get($response, 'code'));
        }

        return $response;
    }

    /**
     * @param $params
     *
     * @return array
     */
    private function prepare($params)
    {
        $params = array_merge([
            'api' => 'asr',
            'appkey' => $this->apiKey,
            'timestamp' => Carbon::now()->getTimestamp(),
            'seq' => 'seg,nli',
            'stop' => 1,
            'compress' => 0,
            'cusid' => '',
            'rq' => [],
        ], $params);

        $params['timestamp'] = $params['timestamp'] * 1000;
        $params['rq'] = is_array($params['rq']) === true ? json_encode($params['rq']) : $params['rq'];
        $params['sign'] = $this->hasher->make($params);

        return $params;
    }
}
