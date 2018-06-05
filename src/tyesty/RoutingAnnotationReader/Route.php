<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;


class Route
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $route;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string[]
     */
    public $middlewares;
}