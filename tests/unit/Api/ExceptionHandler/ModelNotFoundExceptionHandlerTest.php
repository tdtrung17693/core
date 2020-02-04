<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\ExceptionHandler;

use Exception;
use Flarum\Api\ExceptionHandler\ModelNotFoundExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\TestCase;

class ModelNotFoundExceptionHandlerTest extends TestCase
{
    private $handler;

    public function setUp()
    {
        $this->handler = new ModelNotFoundExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new ModelNotFoundException));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new ModelNotFoundException);

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '404',
                'code' => 'resource_not_found'
            ]
        ], $response->getErrors());
    }
}
