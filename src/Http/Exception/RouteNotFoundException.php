<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Exception;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct($message = null, $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
