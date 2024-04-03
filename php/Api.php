<?php

namespace App\Services\Bookhus;

use App\Models\DataSource;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Api
{
    const ENDPOINT = "/api";

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */

    protected $url;
    /**
     * @var string
     */

    protected $userId;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param array|null $credentials
     */
    public function __construct(?array $credentials = [])
    {
        if (empty($credentials)) {
            $dataSource = DataSource::whereKey(DataSource::INTEGRATION_BOOKHUS);
            $params = $dataSource->credentials;
        } else {
            $params = $credentials;
        }

        $this->apiKey = $params['apiKey'];
        $this->url = $params['host'];
        $this->userId = $params['userId'];

        $this->client = new Client();
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function request(string $path, $method = 'GET', $options = [])
    {
        try {
            $options = array_merge([
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ], $options);

            if ($options['query']) {
                $options['query'] .= '&apikey=' . $this->apiKey;
            }

            $endpoint = sprintf("%s%s%s", $this->url, self::ENDPOINT, $path);

            $response = $this->client->request($method, $endpoint, $options);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
