<?php

namespace Atol\Api;

use Atol\Api\Adapter\IlluminateAtolApi\Log\Logger;
use Atol\Api\Exception\SimpleFileCacheException;
use JsonException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class AtolClient - SDK Atol API
 * @package Atol\Api
 */
class AtolClient
{
    /**
     * Предоставляет гибкие методы для синхронного или асинхронного запроса ресурсов HTTP.
     * @var HttpClientInterface|null
     */
    private ?HttpClientInterface $client;

    /**
     * Url аккаунта Atol API
     * @var string
     */
    private string $account;

    /**
     * Токен аккаунта
     * @var string
     */
    private string $token;

    /**
     * Логин пользователя
     * @var string
     */
    private string $userLogin;

    /**
     * Пароль от интеграции в аккаунте
     * @var string
     */
    private string $integrationPassword;

    /**
     * @var CacheInterface|null
     */
    private ?CacheInterface $cache;

    /**
     * SymfonyHttpClient constructor.
     * @param string $account - url аккаунта
     * @param string $login - Логин пользователя
     * @param string $password - Пароль интеграции в аккаунте
     * @param HttpClientInterface|null $client
     * @param CacheInterface|null $cacheInterface - Psr cache
     */
    public function __construct(
        string $account,
        string $login,
        string $password,
        HttpClientInterface $client = null,
        CacheInterface $cacheInterface = null
    ) {
        #Получаем логин для токена
        $this->userLogin = $login;
        #Получаем пароль для токена
        $this->integrationPassword = $password;
        #Получаем ссылку от аккаунта
        $this->account = $account;
        # Сохраняем токен в файловый кэш
        $this->cache = $cacheInterface ?? new SimpleFileCache();

        #HttpClient - выбирает транспорт cURL если расширение PHP cURL включено
        $this->client = $client ?? HttpClient::create(
                [
                    'http_version' => '2.0',
                    'headers' => [
                        'Content-Type' => 'application/json; charset=utf-8'
                    ]
                ]
        );
        if ($this->cache->has('AtolApiToken ' . $this->userLogin)) {
            $this->token = $this->cache->get('AtolApiToken ' . $this->userLogin);
        } else {
            $this->token = $this->getNewToken();
            $this->cache->set('AtolApiToken ' . $this->userLogin, $this->token);
        }
    }

    /**
     * Получаем токен
     * @return string
     * @throws ClientExceptionInterface|DecodingExceptionInterface|ServerExceptionInterface
     * @throws TransportExceptionInterface|RedirectionExceptionInterface
     * @throws SimpleFileCacheException|InvalidArgumentException
     * @throws JsonException
     */
    private function getNewToken(): string
    {
        #Получаем новый токен
        $response = $this->sendRequest(
            "POST",
            "getToken",
            [
                "login" => $this->userLogin,
                "pass" => $this->integrationPassword
            ]
        );

        $token = $response->toArray(false);
        if (!array_key_exists('token', $token)) {
            $textError = $token['error']['text'];
            $this->log('error', $textError, $token);
            throw new JsonException($textError, $response->getStatusCode());
        }
        $this->token = $token["token"];
        return $this->token;
    }

    /**
     * Метод позволяет выполнить запрос к Atol API
     * @param string $method - Метод
     * @param string $model - Модель
     * @param array $params - Параметры
     * @return array - Ответ запроса Atol API
     * @throws ClientExceptionInterface|DecodingExceptionInterface|ServerExceptionInterface
     * @throws TransportExceptionInterface|RedirectionExceptionInterface
     * @throws SimpleFileCacheException|InvalidArgumentException
     * @throws JsonException
     */
    public function request(string $method, string $model, array $params = []): array
    {
        $response = $this->sendRequest($method, $model, $params);
        # Получаем статус запроса
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 200:
                return $response->toArray(false);
            case 400:
                return [
                    'response' => json_decode($response->getContent(false), true),
                    'code' => $statusCode
                ];
            case 401:
                $ffdVersionError = $response->toArray(false);
                if (array_key_exists('result', $ffdVersionError)) {
                    $this->log('error', $ffdVersionError["message"], $ffdVersionError);
                    throw new JsonException($ffdVersionError["message"], $response->getStatusCode());
                }
                $this->token = $this->getNewToken();
                $this->cache->set('AtolApiToken ' . $this->userLogin, $this->token);
                return $this->sendRequest($method, $model, $params)->toArray(false);
            case 500:
                $this->log('critical', "SDK. 500 Internal Server Error", [$response->getContent(false)]);
                throw new JsonException("SDK. 500 Internal Server Error", 500);

            default:
                $this->log('error', "SDK. Ошибка Atol: ", [$response->getContent(false)]);
                throw new JsonException("SDK. Ошибка Atol: " . $response->getContent(false), $response->getStatusCode());
        }
    }

    /**
     * Отправить HTTP запрос - клиентом
     * @param string $method - Метод
     * @param string $model - Модель
     * @param array $params - Параметры
     * @return ResponseInterface
     * @throws InvalidArgumentException|SimpleFileCacheException
     * @throws TransportExceptionInterface
     */
    private function sendRequest(string $method, string $model, array $params = []): ResponseInterface
    {
        #Для получения токена структура запроса: {{url_v4}}/{{possystem}}/{{api_version}}/getToken
        if ($model === "getToken") {
            #Создаем ссылку
            $url = mb_substr($this->account, 0, -2) . $model;
            #Отправляем request запрос
            return $this->client->request(
                strtoupper($method),
                $url,
                [
                    'body' => json_encode($params)
                ]
            );
        }
        #Для получения данных с других запросов: {{url_v4}}/{{possystem}}/{{api_version}}/1/$model
        #Создаем ссылку
        $url = $this->account . $model;
        #Отправляем request запрос
        return $this->client->request(
            strtoupper($method),
            $url,
            [
                'headers' => [
                    'Token' => $this->cache->get('AtolApiToken ' . $this->userLogin)
                ],
                'body' => json_encode($params)
            ]
        );
    }

    /**
     * Метод выполняет запрос на операцию "Приход"
     * @param array $params - Параметры запроса
     * @return array
     * @throws ClientExceptionInterface|DecodingExceptionInterface|ServerExceptionInterface
     * @throws TransportExceptionInterface|RedirectionExceptionInterface
     * @throws SimpleFileCacheException|InvalidArgumentException
     * @throws JsonException
     */
    public function sell(array $params): array
    {
        return $this->request("POST", "sell", $params);
    }

    /**
     * Метод выполняет запрос на операцию "Возврат прихода"
     * @param array $params - Параметры запроса
     * @return array
     * @throws ClientExceptionInterface|DecodingExceptionInterface|ServerExceptionInterface
     * @throws TransportExceptionInterface|RedirectionExceptionInterface
     * @throws SimpleFileCacheException|InvalidArgumentException
     * @throws JsonException
     */
    public function sellRefund(array $params): array
    {
        return $this->request("POST", "sell_refund", $params);
    }

    /**
     * Метод выполняет запрос на операцию "Результат обработки документа"
     * @param string $uuID - Уникальное значение чека
     * @return array
     * @throws ClientExceptionInterface|DecodingExceptionInterface|ServerExceptionInterface
     * @throws TransportExceptionInterface|RedirectionExceptionInterface
     * @throws SimpleFileCacheException|InvalidArgumentException
     * @throws JsonException
     */
    public function report(string $uuID): array
    {
        return $this->request("GET", "report/$uuID");
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $logger = new Logger();
        $logger->$level($message, $context);
    }
}
