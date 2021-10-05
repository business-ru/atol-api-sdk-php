# atol-api-sdk-php

# АТОЛ Онлайн

## О проекте

Данная библиотека предназначена для работы с
сервисом [АТОЛ Онлайн](https://online.atol.ru).

## Требования

* PHP 7.4 и выше
* PHP extension cURL

## Установка

```
composer require business-ru/atol-api-sdk-php
```

Документация: https://online.atol.ru/files/API_atol_online_v4.pdf

## Использование

Добавляем в .env

## Принцип работы

### Создаем файл для работы с Atol Api

```php
<?php
# Подключаем автозагрузку
require 'vendor/autoload.php';
# Подключаем библиотеку Atol Api Client
include 'vendor/business-ru/atol-api-sdk-php/src/AtolClient.php';
# Создание экземпляра класса
$atolApiClient = new AtolClient($this->account, $this->userLogin, $this->integrationPassword);
```

### Примеры использования

#### Приход

```php
<?php
$atolApiClient->sell($params);
```

#### Возврат прихода

```php
<?php
$atolApiClient->sellRefund($params);
```

#### Результат обработки документа

```php
<?php
$atolApiClient->report($uID);
```

