# atol-api-sdk-php

# АТОЛ Онлайн

## О проекте

Данная библиотека предназначена для работы с
сервисом [АТОЛ Онлайн](https://online.atol.ru).

## Требования

* PHP 7.4 и выше
* PHP extension cURL
* Extension Predis

## Установка

```
composer require business-ru/atol-api-sdk-php
```

Документация: https://online.atol.ru/files/API_atol_online_v4.pdf

## Использование

Добавляем в .env

```ini
REDIS_DSN = redis://Наименование контейнера redis:Наименование порта
```

## Пример использования

```php
/**
* Инициализируем класс Atol Api
* @var AtolClient|null
*/
private ?AtolClient $atolClient = null;

/**
* Общий метод, для любой модели
* Метод позволяет выполнить запрос к API OFD
* Для atol-api-sdk-php
* @param string $method - Наименование метода
* @param string $model - Наименование модели
* @param array<string, mixed> $params - Параметры запроса
* @return int|mixed|string[]
* @throws ClientExceptionInterface
* @throws DecodingExceptionInterface
* @throws RedirectionExceptionInterface
* @throws ServerExceptionInterface
* @throws TransportExceptionInterface
* @throws \Exception
*/
public function atolApiRequest(string $method, string $model, array $params = [])
{
	#Создаем объект SDK Atol Api
	$this->atolClient = new AtolClient($this->account, $this->userLogin, $this->integrationPassword);
	#Отправляем запрос
	$this->response = $this->atolClient->request($method, $model, $params);
	return $this->response;
}

```
