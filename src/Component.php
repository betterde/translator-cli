<?php

namespace Betterde\TranslatorCli;

use Exception;
use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Betterde\TranslatorCli\Contracts\Loader;
use Illuminate\Http\Client\RequestException;

/**
 * Component Synchronize handler.
 *
 * Date: 2022/5/30
 * @author George
 * @package Betterde\TranslatorCli
 */
class Component implements Loader
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    public $attributes = [];

    /**
     * Date: 2022/5/30
     * @return array
     * @author George
     */
    public function getElements(): array
    {
        return [];
    }

    public function convert(): array
    {
        return [];
    }

    /**
     * Date: 2022/6/6
     * @param array $attributes
     * @return Component
     * @throws RequestException
     * @throws Exception
     * @author George
     */
    public function sync(array $attributes = []): self
    {
        $client = new Factory();
        $url = sprintf('%s/%s', Certification::$endpoint, 'component/sync');
        $response = $client->withHeaders(['Content-Type' => 'application/json'])
            ->withToken(Certification::$token)
            ->put($url, $attributes)
            ->throw()
            ->json();

        $code = Arr::get($response, 'code');
        if ($code != 200) {
            throw new Exception(Arr::get($response, 'message'));
        }

        $this->attributes = Arr::get($response, 'data.item');

        return $this;
    }

    /**
     * Date: 2022/5/30
     * @param string $path
     * @param string $locale
     * @return array
     * @author George
     */
    public function load(string $path, string $locale): array
    {
        $data = [];
        if (is_file($path)) {
            $__path = $path;
            $__data = $data;

            $data = (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);
                return require $__path;
            })();
        }

        return Arr::dot($data);
    }

    public function addNamespace(string $namespace, string $hint): void
    {
        // TODO: Implement addNamespace() method.
    }

    public function addJsonPath(string $path): void
    {
        // TODO: Implement addJsonPath() method.
    }

    public function namespaces(): array
    {
        return [];
    }

    private function loadFromArray()
    {

    }

    public function loadFromJSON()
    {

    }

    /**
     * Date: 2022/6/6
     * @param string $key
     * @param $default
     * @return array|ArrayAccess|mixed
     * @author George
     */
    public function getAttribute(string $key, $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }
}