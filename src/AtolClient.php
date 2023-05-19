<?php

namespace Atol\Api;

use Atol\Api\Adapter\Cache\SimpleFileCache;
use Atol\Api\Adapter\Log\Logger;
use JsonException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * Class AtolClient - SDK Atol API
 * @package Atol\Api
 */
class AtolClient
{
    /**
     * Токен аккаунта
     * @var string
     */
    private string $token;

    /**
     * SymfonyHttpClient constructor.
     * @param string $account - url аккаунта
     * @param string $userLogin - Логин пользователя
     * @param string $integrationPassword - Пароль интеграции в аккаунте
     * @param HttpClientInterface|null $client - Symfony http клиент
     * @param CacheInterface|null $cache
     */
    public function __construct(
        private readonly string $account,
        private readonly string $userLogin,
        private readonly string $integrationPassword,
        private ?HttpClientInterface $client = null,
        private ?CacheInterface $cache = null
    ) {
        $this->client = $client ?? HttpClient::createForBaseUri($account, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'http_version' => '2.0'
        ]);

        $this->cache = $cache ?? new SimpleFileCache();

        $this->initToken();
    }

    /**
     * Добавление токена в cache
     * @return void
     */
    private function initToken(): void
    {
        if ($this->cache->has('AtolApiToken ' . $this->userLogin)) {
            $this->token = $this->cache->get('AtolApiToken ' . $this->userLogin);
        } else {
            $this->refreshToken();
        }
    }

    private function refreshToken(): void
    {
        $this->token = $this->getNewToken();
        $this->cache->set('AtolApiToken ' . $this->userLogin, $this->token);
    }

    /**
     * Отправить HTTP запрос - клиентом
     * @param string $method - Метод
     * @param string $model - Модель
     * @param array $options - Параметры
     * @return ResponseInterface
     */
    private function sendRequest(string $method, string $model, array $options = []): ResponseInterface
    {
        $method = strtoupper($method);

        $url = $this->account . $model;
        try {
            return $this->client->request($method, $url, $options);
        } catch (Throwable $throwable) {
            $this->refreshToken();
            $options['headers'] = [
                'Token' => $this->cache->get('AtolApiToken ' . $this->userLogin)
            ];
            return $this->client->request($method, $url, $options);
        }
    }

    private function get(string $model, array $options): array
    {
        $options = [
            'headers' => [
                'Token' => $this->cache->get('AtolApiToken ' . $this->userLogin)
            ],
            'query' => $options
        ];
        $response = $this->sendRequest('GET', $model, $options);

        $this->throwStatusCode($response);

        return $response->toArray(false);
    }

    private function post(string $model, array $options): array
    {
        $response = $this->postRequest($model, $options);

        $this->throwStatusCode($response);

        return $response->toArray(false);
    }

    private function postRequest(string $model, array $options): ResponseInterface
    {
        $params = [
            'headers' => [
                'Token' => $this->cache->get('AtolApiToken ' . $this->userLogin)
            ],
            'body' => json_encode($options, JSON_UNESCAPED_UNICODE)
        ];
        return $this->sendRequest('POST', $model, $params);
    }

    private function throwStatusCode(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 400:
            case 422:
            case 200:
                return;
            case 401:
                $this->log('debug', 'Токен просрочен.', [
                    'response' => $response->toArray(false),
                    'status_code' => $response->getStatusCode(),
                ]);

                $this->refreshToken();
                return;
            case 500:
                $this->log('critical', "SDK. Ошибка Atol Api. 500 Internal Server Error", $response->toArray(false));
                throw new ServerException($response);
            default:
                $this->log('error', "SDK. Ошибка Atol Api: ", $response->toArray(false));
                throw new JsonException($response->getContent(false), $statusCode);
        }
    }

    /**
     * Получаем токен
     * @return string
     */
    private function getNewToken(): string
    {
        try {
            $model = 'getToken';
            $method = 'POST';
            # Для получения токена структура запроса: {{url_v4}}/{{possystem}}/{{api_version}}/getToken
            $url = mb_substr($this->account, 0, -2) . $model;
            $options = [
                "login" => $this->userLogin,
                "pass" => $this->integrationPassword
            ];
            $params = [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($options)
            ];
            $request = $this->client->request($method, $url, $params)->toArray(false);
            $this->token = $request['token'];
        } catch (Throwable $throwable) {
            $this->log('error', 'Ошибка при получении токена', [
                'code' => $throwable->getCode(),
                'line' => $throwable->getLine(),
                'message' => $throwable->getMessage()
            ]);
            throw new JsonException($throwable->getMessage(), $throwable->getCode());
        }
        return $this->token;
    }

    /**
     * Метод выполняет запрос на операцию "Приход"
     * @param array $params - Параметры запроса
     * @return array
     */
    public function sell(array $params): array
    {
        return $this->post("sell", $params);
    }

    /**
     * Метод выполняет запрос на операцию "Возврат прихода"
     * @param array $params - Параметры запроса
     * @return array
     */
    public function sellRefund(array $params): array
    {
        return $this->post("sell_refund", $params);
    }

    /**
     * Метод выполняет запрос на операцию "Результат обработки документа"
     * @param string $uuID - Уникальное значение чека
     * @return array
     */
    public function report(string $uuID): array
    {
        return $this->get("report/" . $uuID, []);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $logger = new Logger();
        $logger->$level($message, $context);
    }
}
