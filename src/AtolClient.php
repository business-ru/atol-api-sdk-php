<?php

namespace Atol\Api;

class AtolClient extends SymfonyHttpClient
{

	/**
	 * @param string $method Метод
	 * @param string $model Модель
	 * @param array $params Параметры
	 * @return array
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 * @throws \Atol\Api\Exception\AtolApiClientException
	 */
	public function request(string $method, string $model, array $params = []): array
	{
		return $this->sendRequest(strtoupper($method), $model, $params);
	}


}
