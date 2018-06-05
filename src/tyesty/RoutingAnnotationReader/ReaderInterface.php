<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;

/**
 * Reader Interface
 *
 * This interface has to be implemented when creating a new reader class.
 *
 * @package tyesty\RoutingAnnotationReader
 */
interface ReaderInterface
{
    /**
     * Main reader running method
     *
     * @return void
     */
    public function run(): void;

    /**
     * Returns the list of routes found by the reader.
     *
     * Make sure that this method always returns either an empty array or a list of Route objects
     * @return Route[]
     */
    public function getRoutes(): array;
}