<?php

namespace Esyede;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Curl;
use System\Config;
use System\Request;

class Hcaptcha
{
    protected static $responses = [];

    public static function show(array $attributes = [])
    {
        return '<div ' . static::build($attributes) . '></div>';
    }

    public static function button($form_id, $text = 'Captcha', array $attributes = [])
    {
        $script = '';

        if (! isset($attributes['data-callback'])) {
            $fn = 'onSubmit' . Str::studly(preg_replace('/[^a-zA-Z0-9_-]/', '', $form_id));
            $attributes['data-callback'] = $fn;
            $script = sprintf('<script>function %s(){document.getElementById("%s").submit();}</script>', $fn, $form_id);
        }

        return sprintf('<button %s><span>%s</span></button>', static::build($attributes), $text) . $script;
    }

    public static function check($response)
    {
        $response = is_string($response) ? trim($response) : '';

        if ($response === '') {
            return false;
        }

        if (array_key_exists($response, static::$responses)) {
            return static::$responses[$response];
        }

        $payloads = [
            'secret' => Config::get('hcaptcha::main.secret'),
            'response' => $response,
            'remoteip' => Request::ip(),
        ];

        try {
            $result = Curl::post('https://hcaptcha.com/siteverify', [], $payloads);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $success = isset($result->body)
            && is_object($result->body)
            && isset($result->body->success)
            && $result->body->success;

        static::$responses[$response] = (bool) $success;

        return static::$responses[$response];
    }

    public static function js($lang = null)
    {
        $lang = ($lang ? $lang : Config::get('application.language'));
        return '<script src="https://hcaptcha.com/1/api.js?hl=' . $lang . '" async defer></script>' . PHP_EOL;
    }

    protected static function build(array $attributes)
    {
        $attributes = array_filter($attributes);
        $attributes['data-sitekey'] = Config::get('hcaptcha::main.sitekey');
        $attributes['class'] = str_replace('h-captcha', '', isset($attributes['class']) ? $attributes['class'] : '');
        $attributes['class'] = trim('h-captcha ' . $attributes['class']);
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key . '="' . $value . '"';
        }

        return trim(implode(' ', $html));
    }
}
