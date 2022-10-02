<?php

defined('DS') or exit('No direct script access.');

System\Autoloader::map(['Esyede\Hcaptcha' => __DIR__.DS.'libraries'.DS.'hcaptcha.php']);
System\Validator::register('hcaptcha', function ($attribute, $value) {
    return Esyede\Hcaptcha::check($value);
});
