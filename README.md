# atol-api-sdk-php

# АТОЛ Онлайн

## О проекте

Данная библиотека предназначена для работы с сервисом [АТОЛ Онлайн](https://online.atol.ru).

## Требования

* PHP 7.4 и выше
* PHP extension cURL
* Extension Predis

## Установка

```
composer require hotdog666/atol-api-sdk-php
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

```