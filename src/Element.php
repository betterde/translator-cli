<?php

namespace Betterde\TranslatorCli;

use Exception;
use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;

class Element
{
    const STATE_ENABLE = 1;
    const STATE_DISABLED = 2;

    private string $module;

    private string $locale;

    private array $attributes;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Date: 2022/6/6
     * @return Element
     * @throws RequestException
     * @author George
     */
    public function sync(): self
    {
        $client = new Factory();
        $url = sprintf('%s/%s', Certification::$endpoint, 'element');
        $response = $client->withHeaders(['Content-Type' => 'application/json'])
            ->withToken(Certification::$token)
            ->post($url, $this->attributes)
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

    /**
     * Get element attributes.
     *
     * Date: 2022/6/6
     * @return array
     * @author George
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}