<?php

declare(strict_types=1);

namespace app\Util;

use Imi\Util\Traits\TStaticClass;

class Generator
{
    use TStaticClass;

    public static function generateToken(int $length = 32): string
    {
        return base64_encode(random_bytes($length));
    }

    public static function generateCode(int $length = 6): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', \STR_PAD_LEFT);
    }
}
