<?php

namespace Abovesky\Annotation\Validate\exception;

use SoloCms\exception\BaseException;

class ValidateException extends BaseException
{
    public $code = 400;
    public $message = '参数错误';
    public $error_code = 99999;
}