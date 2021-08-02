<?php

namespace Atol\Api;

use Atol\Api\Exception\AtolApiClientException;
use JsonException;
use Predis\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
	 * @var string|null
	 */
	private ?string $token;

	/**
	 * Логин пользователя
	 * @var string|null
	 */
	private ?string $userLogin;

	/**
	 * Пароль от интеграции в аккаунте
	 * @var string|null
	 */
	private ?string $integrationPassword;

	/**
	 * SymfonyHttpClient constructor.
	 * @param string $account - url аккаунта
	 * @param string|null $login - Логин пользователя
	 * @param string|null $password -  Пароль интеграции в аккаунте
	 * @param HttpClientInterface|null $client
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws JsonException
	 */
	public function __construct(string $account, string $login, string $password, HttpClientInterface $client = null)
	{
		#Получаем логин для токена
		$this->userLogin = $login;
		#Получаем пароль для токена
		$this->integrationPassword = $password;
		#Подключение к redis
		$cacheRedis = RedisAdapter::createConnection(
			getenv('REDIS_DSN'),
			['class' => Client::class, 'timeout' => 3]
		);
		#Получаем ссылку от аккаунта
		$this->account = $account;
		#HttpClient - выбирает транспорт cURL если расширение PHP cURL включено
		$this->client = $client ?? HttpClient::create(
				[
					'http_version' => '2.0',
					'headers' => [
						'Content-Type' => 'application/json; charset=utf-8',
					]
				]
			);
		#Проверяем, есть токен в cache или нет
		if (!$cacheRedis->exists('atolTokenCache' . $this->account)) {
			#Добавляем токен в cache на 10 часов
			$cacheRedis->setex('atolTokenCache' . $this->account, 83000, $this->getNewToken());
			#Получаем текущий токен
			$this->token = $cacheRedis->get('atolTokenCache' . $this->account);
		}
		#Добавляем в header токен из cache
		$this->client = HttpClient::create(
			[
				'http_version' => '2.0',
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
					'Token' => $cacheRedis->get('atolTokenCache' . $this->account)
				]
			]
		);
	}

	/**
	 * Получаем токен
	 * @return string
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws JsonException
	 */
	private function getNewToken(): string
	{
		#Получаем новый токен
		$token = $this->request(
			"POST",
			"getToken",
			[
				"login" => $this->userLogin,
				"pass" => $this->integrationPassword
			]
		);
		$this->token = $token["token"];
		return $this->token;
	}

	/**
	 * Метод позволяет выполнить запрос к Atol API
	 * @param string $method - Метод
	 * @param string $model - Модель
	 * @param array $params - Параметры
	 * @return array|string
	 * @throws JsonException
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function request(string $method, string $model, array $params = [])
	{
		#Для получения токена структура запроса: {{url_v4}}/{{possystem}}/{{api_version}}/getToken
		if ($model === "getToken") {
			#Создаем ссылку
			$url = mb_substr($this->account, 0, -2) . $model;
			#Для получения данных с других запросов: {{url_v4}}/{{possystem}}/{{api_version}}/1/$model
		} else {
			#Создаем ссылку
			$url = $this->account . $model;
		}
		#Отправляем request запрос
		$response = $this->client->request(
			strtoupper($method),
			$url,
			['body' => json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]
		);
		#Получаем статус запроса
		$statusCode = $response->getStatusCode();
		if ($statusCode === 200) {
			return json_decode(
				$response->getContent(false),
				true,
				512,
				JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
			);
		}
		#false - убрать throw от Symfony.....
		return $response->toArray(false);
	}
}
