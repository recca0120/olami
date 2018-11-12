<?php

namespace Recca0120\Olami;

use Carbon\Carbon;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\CookieJar;
use Http\Message\MessageFactory;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use PharIo\Manifest\AuthorCollection;

class Client
{
    private $endpoint = 'https://tw.olami.ai/cloudservice/api';
    private $apiKey;
    private $hasher;
    private $client;
    private $messageFactory;

    /**
     * Client constructor.
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
     * @param $params
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function query($params)
    {
        if (empty($params['sound']) === false) {
            $response = $this->sendFile($params);
            unset($params['sound']);

            if ($response['status'] === 'ok') {
                do {
                    $response = $this->sendRequest(array_merge($params, [
                        'stop' => 1,
                    ]));
                } while ($this->isfinished($response) !== true);

                return $response;
            }

            throw new HttpException;
        }

        return $this->sendRequest($params);
    }

    /**
     * @param $response
     * @return bool
     */
    private function isFinished($response)
    {
        if (Arr::get($response, 'data.asr.status') >= 2) {
            throw new HttpException(Arr::get($response, 'data.asr.msg'));
        }

        $finished = (bool)Arr::get($response, 'data.asr.final') === true;

        if ($finished === false) {
            sleep(2);
        }

        return $finished;
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Http\Client\Exception
     */
    private function sendFile($params)
    {
        $params = $this->prepareParams($params);
        list($boundary, $multipartStream) = $this->buildStream($params);

        return $this->decode(
            $this->messageFactory->createRequest(
                'POST',
                $this->endpoint,
                ['Content-Type' => 'multipart/form-data; boundary="' . $boundary . '"'],
                $multipartStream
            )
        );
    }

    /**
     * @param $params
     * @return mixed
     * @throws \Http\Client\Exception
     */
    private function sendRequest($params)
    {
        return $this->decode(
            $this->messageFactory->createRequest(
                'GET',
                $this->endpoint . '?' . http_build_query($this->prepareParams($params))
            )
        );
    }

    /**
     * @param $params
     * @return array
     */
    private function prepareParams($params)
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

    /**
     * @param $params
     * @return array
     */
    private function buildStream($params)
    {
        $builder = new MultipartStreamBuilder();

        foreach ($params as $key => $value) {
            file_exists($value) === true
                ? $builder->addResource($key, fopen($value, 'r'), ['filename' => $value])
                : $builder->addResource($key, $value);
        }

        return [$builder->getBoundary(), $builder->build()];
    }

    /**
     * @param $request
     * @return array
     * @throws \Http\Client\Exception
     */
    private function decode($request)
    {
        $response = $this->client->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }
}
