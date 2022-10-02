<?php

namespace Esyede;

use System\Str;
use System\Curl;
use System\Input;
use System\Config;
use System\Request;

class Hcaptcha
{
    protected static $responses = [];

    public static function show(array $attributes = [])
    {
        return '<div '.static::build($attributes).'></div>';
    }

    public static function button($form_id, $text = 'Captcha', array $attributes = [])
    {
        $script = '';

        if (! isset($attributes['data-callback'])) {
            $fn = 'onSubmit'.Str::studly(str_replace(['=', '\'', '"', '<', '>', '`'], '', $form_id));
            $attributes['data-callback'] = $fn;
            $script = sprintf('<script>function %s(){document.getElementById("%s").submit();}</script>', $fn, $form_id);
        }

        return sprintf('<button %s><span>%s</span></button>', static::build($attributes), $text).$script;
    }

    public static function check($response)
    {
        if (empty($response)) {
            return false;
        }

        if (in_array($response, static::$responses)) {
            return true;
        }

        $payloads = [
            'secret' => Config::get('hcaptcha::main.secret'),
            'response' => Input::get('h-captcha-response'),
            'remoteip' => Request::ip(),
        ];

        $response = Curl::post('https://hcaptcha.com/siteverify', $payloads);
        \System\Storage::put(path('storage').'test.json', json_encode($response, JSON_PRETTY_PRINT));

        if (! isset($response->body) || ! isset($response->body->success) || ! $response->body->success) {
            return false;
        }

        static::$responses[] = $response;
        return true;
    }

    public static function js($lang = null)
    {
        $lang = ($lang ? $lang : Config::get('application.language'));
        return '<script src="https://hcaptcha.com/1/api.js?hl='.$lang.'" async defer></script>'.PHP_EOL;
    }

    protected static function build(array $attributes)
    {
        $attributes = array_filter($attributes);
        $attributes['data-sitekey'] = Config::get('hcaptcha::main.sitekey');
        $attributes['class'] = str_replace('h-captcha', '', isset_or($attributes['class'], ''));
        $attributes['class'] = trim('h-captcha '.$attributes['class']);
        $html = [];

        foreach ($attributes as $key => $value) {
            $html[] = $key.'="'.$value.'"';
        }

        return trim(implode(' ', $html));
    }
}
