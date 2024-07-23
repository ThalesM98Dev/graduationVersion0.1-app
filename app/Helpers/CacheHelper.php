<?php

// CacheHelper.php

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function cacheData($key, $value, $expiration)
    {
        cache([$key => $value], $expiration);
    }
}