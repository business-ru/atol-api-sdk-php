<?php

namespace Atol\Api;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @method  withOptions(array $options)
 */
class AtolClient
{
	/**
	 * @var \Symfony\Contracts\HttpClient\HttpClientInterface|null
	 */
	protected ?HttpClientInterface $client;

	/**
	 * @var string
	 * Url аккаунта
	 */
	protected string $account;

	/**
	 * @var string
	 * Модель аккаунта
	 */
	protected string $model;

	/**
	 * @var false|mixed|null|string
	 * Токен
	 */
	protected $token;

	/**
	 * @var string|null
	 * Логин пользователя
	 */
	protected ?string $userLogin;

	/**
	 * @var string|null
	 * Пароль от интеграции
	 */
	protected ?string $integrationPassword;

	/**
	 * @var object
	 * Объект для работы с кэшем
	 */
	protected object $cache;

	/**
	 * SymfonyHttpClient constructor.
	 * @param string $method
	 * @param string $account
	 * @param string $model
	 * @param string|null $login
	 * @param string|null $password
	 * @param \Symfony\Contracts\HttpClient\HttpClientInterface|null $decoratedClient
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 * @throws \JsonException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function __construct(
		string $account,
		string $model,
		string $login = null,
		string $password = null,
		HttpClientInterface $client = null
	) {
		#Подключение к redis
		$this->cache = RedisAdapter::createConnection('redis://localhost:6379');
		#Получаем ссылку от аккаунта
		$this->account = $account;
		#Получаем модель
		$this->model = $model;
		#Получаем логин пользователя
		$this->userLogin = $login;
		#Получаем пароль от интеграции
		$this->integrationPassword = $password;
		#Проверяем, есть токен в cache или нет
		if ($this->cache->exists('tokenCache')) {
			#Добавляем токен в cache на 24 часа
			$this->token = $this->cache->setex('tokenCache', 86400, $this->getNewToken());
		} else {
			#Получаем текущий токен
			$this->token = $this->cache->get('tokenCache');
		}
		#Подключаем HTTP запросы TODO
		$this->client = $client ?? HttpClient::create(
				[
					'http_version' => '2.0',
					'headers' => [
						'Content-Type' => 'application/json; charset=utf-8',
						'Token' => $this->token
					]
				]
			);
	}

	/**
	 * @param string $method - Метод
	 * @param string $model - Модель
	 * @param array $params - Параметры
	 * @return array
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \JsonException
	 */
	public function request(string $method, string $model, array $params = []): array
	{
		return $this->sendRequest($method, $model, $params);
	}

	/**
	 *
	 * @param string $method
	 * @param string $model
	 * @param array $params
	 * @return array
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \JsonException
	 */
	private function sendRequest(string $method, string $model, array $params = []): array
	{
		$uri = HttpClient::createForBaseUri($this->account);
//		var_dump($uri);
		$request = $uri->request(strtoupper($method), $model, $params);
		$statusCode = $request->getStatusCode();
		if ($statusCode === 200) {
			$response = $request->getContent();
			$result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
			return $result;
		}
		if ($statusCode === 401) {
			return ["Статус запроса" => 401];
		}
		return ["status" => "error", "error_code" => "http:" . $statusCode];
	}

	private function sendTokenRequest(string $method,array $params = []): string
	{
		$uri = HttpClient::createForBaseUri("");
	}

	/**
	 * Получаем токен
	 * @return string
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \JsonException
	 */
	protected function getNewToken(): string
	{
		$token = $this->sendTokenRequest(
			"POST",
			[
				"login" => $this->userLogin,
				"pass" => $this->integrationPassword
			]
		)["token"];
		$this->token = $token;
		return $this->token;
	}

//	/**
//	 * Восстановить токен
//	 * Отправит запрос к API на восстановление токена
//	 * в случае успеха вернет строку с токеном
//	 * @return string|array Токен
//	 * @throws \Atol\Api\Exception\AtolApiClientException
//	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
//	 */
//	protected function getToken(): string
//	{
//		$result = $this->sendRequest(
//			'GET',
//			$this->account,
//			'getToken',
//			[
//				"login" => $this->userLogin,
//				"pass" => $this->integrationPassword
//			]
//		);
//		if (isset($result['token']) && is_string($result['token'])) {
//			$this->token = $result['token'];
//			var_dump($this->token);
//			return $this->token;
//		}
//
//		$errorMessage = 'Не удалось получить токен.';
//
//		if (is_array($result) && isset($result['error']) && !empty($result['error'])) {
//			$errorMessage .= ' Код ошибки: ' . $result['error'];
//		}
//		throw new AtolApiClientException($errorMessage);
//	}
	/**
	 * Yields responses chunk by chunk as they complete.
	 *
	 * @param ResponseInterface|ResponseInterface[]|iterable $responses One or more responses created by the current HTTP client
	 * @param float|null $timeout The idle timeout before yielding timeout chunks
	 * @return \Symfony\Contracts\HttpClient\ResponseStreamInterface
	 */
	public function stream($responses, float $timeout = null): ResponseStreamInterface
	{
		return $this->client->stream($responses, $timeout);
	}

	public function __call($name, $arguments)
	{
		// TODO: Implement @method  withOptions(array $options)
	}
}
