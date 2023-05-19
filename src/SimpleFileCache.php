<?php

namespace Atol\Api;

use DateInterval;
use Atol\Api\Exception\SimpleFileCacheException;
use Psr\SimpleCache\CacheInterface;

/**
 * Класс для работы с сохранением данных в файлах
 */
final class SimpleFileCache implements CacheInterface
{
    /**
     * Домашняя директория библиотеки
     * @var string
     */
    private string $cachePath = __DIR__ . DIRECTORY_SEPARATOR . 'cache';

    /**
     * SimpleFileCache constructor.
     * @throws SimpleFileCacheException
     */
    public function __construct()
    {
        if (!is_dir($this->cachePath)
            && !mkdir($concurrentDirectory = $this->cachePath)
            && !is_dir($concurrentDirectory)) {
            throw new SimpleFileCacheException('Невозможно создать директорию для хранения кэша /src/cache/');
        }
    }

    /**
     * Получение значения по ключу
     * @param string $key - Уникальный ключ этого элемента в кеше.
     * @param mixed|null $default - Значение по умолчанию, возвращаемое, если ключ не существует.
     * @return string|bool
     * @throws SimpleFileCacheException
     */
    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new SimpleFileCacheException('Ключ должен быть строкой');
        }
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . $key;

        //Нет прав для чтения
        if (!is_readable($cacheFile)) {
            throw new SimpleFileCacheException('Недостаточно прав для чтения кэша /src/cache/');
        }

        //Нет кеша с полученным ключом
        if (!file_exists($cacheFile)) {
            throw new SimpleFileCacheException('Нет кеша с полученным ключом');
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Запись в файл
     * @param string $key - Уникальный ключ этого элемента в кеше.
     * @param $value - Значение в кэше
     * @param null|int|DateInterval $ttl - Время хранения значения в секундах
     * @return bool
     * @throws SimpleFileCacheException
     */
    public function set($key, $value, $ttl = null): bool
    {
        if (!is_string($key)) {
            throw new SimpleFileCacheException('Ключ должен быть строкой');
        }

        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . $key;

        //Нет прав для записи
        if (!is_writable($this->cachePath)) {
            throw new SimpleFileCacheException('Недостаточно прав для записи кэша /src/cache/');
        }

        if (file_put_contents($cacheFile, $value)) {
            return true;
        }
        return false;
    }

    /**
     * Удаление значения в кэше
     * @param string $key - Уникальный ключ кеша удаляемого элемента.
     * @return bool
     */
    public function delete($key): bool
    {
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . $key;
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        return false;
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    /**
     * Проверка на существование ключа
     * @param string $key - Уникальный ключ этого элемента в кеше.
     * @throws SimpleFileCacheException
     */
    public function has($key): bool
    {
        if (!is_string($key)) {
            throw new SimpleFileCacheException('Ключ должен быть строкой');
        }
        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . $key;

        if (file_exists($cacheFile)) {
            return true;
        }

        return false;
    }
}
