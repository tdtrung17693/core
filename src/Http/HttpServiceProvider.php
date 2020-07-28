<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\FrontendFormatter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.http.csrfExemptPaths', function () {
            return ['/api/token'];
        });

        $this->app->bind(Middleware\CheckCsrfToken::class, function ($app) {
            return new Middleware\CheckCsrfToken($app->make('flarum.http.csrfExemptPaths'));
        });

        $this->app->singleton('flarum.http.frontend_exceptions', function () {
            return [
                NotAuthenticatedException::class, // 401
                PermissionDeniedException::class, // 403
                ModelNotFoundException::class, // 404
                RouteNotFoundException::class, // 404
            ];
        });

        $this->app->singleton('flarum.http.frontend_handler', function () {
            return new Middleware\HandleErrors(
                $this->app->make(Registry::class),
                $this->app['flarum']->inDebugMode() ? $this->app->make(WhoopsFormatter::class) : $this->app->make(FrontendFormatter::class),
                $this->app->tagged(Reporter::class),
                $this->app->make('flarum.http.frontend_exceptions')
            );
        });
    }
}
