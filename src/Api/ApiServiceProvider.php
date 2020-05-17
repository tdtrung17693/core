<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('api', $this->app->make('flarum.api.routes'), 'api');
        });

        $this->app->singleton('flarum.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->app->singleton('flarum.api.floodCheckers', function () {
            return [
                [
                    'paths' => ['*'],
                    'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'callback' => function ($actor, $request) {
                        if ($request->getAttribute('bypassFloodgate')) {
                            return false;
                        }
                    }
                ]
            ];
        });

        $this->app->bind(Middleware\Floodgate::class, function ($app) {
            $floodCheckers = array_map(function ($element) use ($app) {
                if (is_string($element['callback'])) {
                    $element['callback'] = $app->make($element['callback']);
                }

                return $element;
            } $app->make('flarum.api.floodCheckers'));

            return new Middleware\Floodgate($floodCheckers);
        });

        $this->app->singleton('flarum.api.middleware', function () {
            return [
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\Floodgate::class,
                HttpMiddleware\SetLocale::class,
            ];
        });

        $this->app->singleton('flarum.api.handler', function () {
            $pipe = new MiddlewarePipe;

            $pipe->pipe(new HttpMiddleware\HandleErrors(
                $this->app->make(Registry::class),
                new JsonApiFormatter($this->app['flarum']->inDebugMode()),
                $this->app->tagged(Reporter::class)
            ));

            foreach ($this->app->make('flarum.api.middleware') as $middleware) {
                $pipe->pipe($this->app->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.api.routes')));

            return $pipe;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->registerNotificationSerializers();

        AbstractSerializeController::setContainer($this->app);
        AbstractSerializeController::setEventDispatcher($events = $this->app->make('events'));

        AbstractSerializer::setContainer($this->app);
        AbstractSerializer::setEventDispatcher($events);
    }

    /**
     * Register notification serializers.
     */
    protected function registerNotificationSerializers()
    {
        $blueprints = [];
        $serializers = [
            'discussionRenamed' => BasicDiscussionSerializer::class
        ];

        $this->app->make('events')->dispatch(
            new ConfigureNotificationTypes($blueprints, $serializers)
        );

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->app->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
