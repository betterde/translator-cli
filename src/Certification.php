<?php

namespace Betterde\TranslatorCli;

use Illuminate\Support\Arr;

/**
 * Date: 2022/6/5
 * @author George
 * @package Betterde\TranslatorCli
 */
class Certification
{
    static public string $endpoint;

    static public string $project;

    static public string $token;

    /**
     * Load certification config from local json file.
     *
     * Date: 2022/6/5
     * @param string $path
     * @author George
     */
    public static function load(string $path): void
    {
        $certFile = sprintf('%s/%s', $path, 'translator-auth.json');

        if (file_exists($certFile)) {
            $certification = json_decode(file_get_contents($certFile), JSON_UNESCAPED_UNICODE);
            self::$endpoint = Arr::get($certification, 'endpoint');
            self::$project = Arr::get($certification, 'project');
            self::$token = Arr::get($certification, 'token');
        } else {
            warn('Please use the auth command to generate the authentication information first.');
            message('e.g. translator auth <domain> <project> <token>');
            exit(1);
        }
    }

    /**
     * Write certification config into local json file.
     *
     * Date: 2022/6/5
     * @param string $endpoint
     * @param string $project
     * @param string $token
     * @author George
     */
    public static function write(string $endpoint, string $project, string $token): void
    {
        $identity = [
            'endpoint' => $endpoint,
            'project' => $project,
            'token' => $token
        ];

        $filename = 'translator-auth.json';

        $certification = json_encode($identity, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (empty($force) && file_exists($filename)) {
            warn('The certification file for the translator service already exists!');
        } else {
            file_put_contents($filename, $certification);
        }
    }
}