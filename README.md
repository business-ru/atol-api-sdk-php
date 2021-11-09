# atol-api-sdk-php

# Бизнес.Ру Онлайн-чеки

## О проекте

Данная библиотека предназначена для работы с
сервисом [Бизнес.Ру Онлайн-чеки](https://online-check.business.ru/).

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
$params = [
    "timestamp" => date("d.m.Y H:i:s"),
    "external_id" => uniqid('', true),
    "receipt" => [
        "client" => [
            "email" => "test@test.ru"
        ],
        "sno" => "osn",
        "items" => [
            [
                "name" => "Чек Прихода №1",
                "price" => 1,
                "quantity" => 1,
                "sum" => 1,
                "measurement_unit" => "гр.",
                "payment_method" => "full_prepayment",
                "payment_object" => "payment",
                "vat" => [
                    "type" => "vat20",
                ]
            ]
        ],
        "payments" => [
            [
                "type" => 1,
                "sum" => 1
            ]
        ],
        "total" => 1
    ]
];
$sell = $atolApiClient->sell($params);
```

#### Возврат прихода

```php
<?php
$params = [
    "timestamp" => date("d.m.Y H:i:s"),
    "external_id" => uniqid('', true),
    "receipt" => [
        "client" => [
            "email" => "test@test.ru"
        ],
        "sno" => "osn",
        "items" => [
            [
                "name" => "Чек Прихода №1",
                "price" => 1,
                "quantity" => 1,
                "sum" => 1,
                "measurement_unit" => "гр.",
                "payment_method" => "full_prepayment",
                "payment_object" => "payment",
                "vat" => [
                    "type" => "vat20",
                ]
            ]
        ],
        "payments" => [
            [
                "type" => 1,
                "sum" => 1
            ]
        ],
        "total" => 1
    ]
];
$sellRefund = $atolApiClient->sellRefund($params);
```

#### Результат обработки документа

```php
<?php
$uuID = $sellRefund["uuid"];
$atolApiClient->report($uuID);
```

