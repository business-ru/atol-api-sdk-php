<?php

namespace Atol\Api;

use Atol\Api\Exception\AtolApiClientException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SymfonyHttpClient
{
	/**
	 * @var string
	 * Url аккаунта
	 */
	protected string $urlAccount;

	/**
	 * @var string
	 * Токен
	 */
	protected string $token;

	/**
	 * @var string
	 * Логин пользователя
	 */
	protected string $userLogin;

	/**
	 * @var string
	 * Пароль от интеграции
	 */
	protected string $integrationPassword;

	/**
	 * @var object
	 * Объект для работы с кэшем
	 */
	protected object $cache;

	/**
	 * @var HttpClientInterface|null
	 *  Http - клиент
	 */
	protected ?HttpClientInterface $client;


	/**
	 * SymfonyHttpClient constructor.
	 * @param string $urlAccount
	 * @param \Symfony\Contracts\Cache\ItemInterface $cache
	 * @param \Symfony\Contracts\HttpClient\HttpClientInterface|null $client
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 * @throws \JsonException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function __construct(string $urlAccount, ItemInterface $cache, HttpClientInterface $client = null)
	{
		$this->client = $client;
		$this->urlAccount = $urlAccount;
		$this->cache = $cache;
		$this->client = $client;

		if ($this->cache->isHit()) {
			$this->token = $this->cache->get();
		} else {
			$this->token = $this->getNewToken();
			$this->cache->set($this->token);
		}
	}

	/**
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 */
	public function sendRequest(string $method, string $url, array $params = []): array
	{

		$response = $this->client->request($method, $url, [
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
				'Token:'  => $this->getToken()
			],
		]);
		return $response->toArray();
	}

	/**
	 * Получаем токен
	 * @return string
	 * @throws \JsonException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 */
	private function getNewToken(): string
	{
		$token = json_encode(
			$this->sendRequest(
				"POST",
				"getToken",
				[
					"login" => $this->userLogin,
					"pass" => $this->integrationPassword
				]
			),
			JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
		)["token"];
		$this->token = $token;
		return $this->token;
	}

	/**
	 * Восстановить токен
	 * Отправит запрос к API на восстановление токена
	 * в случае успеха вернет строку с токеном
	 * @return string|array Токен
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	private function getToken(): string
	{
		$this->token = '';
		$result = $this->sendRequest(
			'GET',
			'getToken',
			[
				"login" => $this->userLogin,
				"pass" => $this->integrationPassword
			]
		);
		if (isset($result['token']) && is_string($result['token']) && strlen($result['token']) === 32) {
			return $result['token'];
		}

		$errorMessage = 'Не удалось получить токен.';

		if (is_array($result) && isset($result['error_code']) && !empty($result['error_code'])) {
			$errorMessage .= ' Код ошибки: ' . $result['error_code'];
		}
		throw new AtolApiClientException($errorMessage);
	}
}