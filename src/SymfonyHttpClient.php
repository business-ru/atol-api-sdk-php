<?php

namespace Atol\Api;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;

class SymfonyHttpClient
{
//	/**
//	 * @param string $method
//	 * @param string $account
//	 * @param string $model
//	 * @param $params
//	 * @return array|false|resource|string
//	 * @throws \Atol\Api\Exception\AtolApiClientException
//	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
//	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
//	 */
//	public function sendRequest(string $method,string $model,array $params = []): array
//	{
//		$response = $this->client->request('GET', 'https://...');
////		$response = $this->client->request(
////			strtoupper($method),
////			$model,
////			['json' => $params]
////		);
////		var_dump($response->getContent());
////		return $response->getContent();
//	}




	public function sendRequest(Request $request): void
	{
		$uri = $request->getUri();
		var_dump($uri);
		$method = $request->getMethod();
		var_dump($method);
		$params = $request->query->keys();
	}


}
