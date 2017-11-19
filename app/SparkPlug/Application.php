<?php declare(strict_types = 1);
/**
 * User: Hannes
 * Date: 20.10.2017
 * Time: 17:17
 */

namespace App\SparkPlug;

use App\SparkPlug\Exceptions\ClassNotFoundException;
use App\SparkPlug\Request\Request;
use App\SparkPlug\Response\ResponseInterface;
use App\SparkPlug\Routing\Exceptions\RouteNotFoundException;
use App\SparkPlug\Views\View;

/**
 * Application Container
 * Einstiegspunkt in gesamte App
 * Verwaltet in der APp benötigte Singletons und sendet Ausgaben an den Browser
 *
 * @package App\SparkPlug
 */
class Application
{
    private static $instance;
    private $basePath;
    private $resolvedSingletons = [];

    /**
     * Application constructor.
     *
     * @param string $basePath Pfad zu App Ordner
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        static::$instance = $this;
    }

    /**
     * Gibt eine Instanz der App zurück
     *
     * @return \App\SparkPlug\Application
     */
    public static function getInstance(): Application
    {
        return static::$instance;
    }

    /**
     * Registriert einen Klassennamen als Singleton und erstellt eine Instanz
     *
     * @param string $className
     */
    public function singleton(string $className): void
    {
        $this->resolvedSingletons[$className] = new $className();
    }

    /**
     * Instanziert eine Klasse des gegebenen Namens, oder gibt eine Instanz zurück, wenn Klassenname als Singleton
     * registriert wurde
     *
     * @param string $className Name der Klasse
     * @param bool   $new       Ignoriert registrierte Singletons
     *
     * @return mixed
     * @throws \App\SparkPlug\Exceptions\ClassNotFoundException
     */
    public function make(string $className, bool $new = false)
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException("Class {$className} not defined or loaded!");
        }

        if (isset($this->resolvedSingletons[$className]) && !$new) {
            return $this->resolvedSingletons[$className];
        }

        return new $className();
    }

    /**
     * Verarbeitet eingehende Requests
     *
     * @param \App\SparkPlug\Request\Request $request Aktueller Request
     *
     * @return \App\SparkPlug\Response\ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        /** @var \App\SparkPlug\Routing\Router $router */
        $router = $this->make(\App\SparkPlug\Routing\Router::class);

        try {
            /** @var \App\SparkPlug\Routing\Route $route */
            $route = $router->match($request);
        } catch (RouteNotFoundException $e) {
            return new View('errors.404', 404);
        }

        /** @var \App\SparkPlug\Controllers\AbstractController $controller */
        $controller = $this->make($route->getController());
        $controller->setRequest($request);

        return call_user_func_array([$controller, $route->getMethod()], $route->getArguments());
    }

    /**
     * @return string Basepath der App
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Registriert Routen in den Router
     *
     * @throws \ErrorException
     */
    public function loadRoutes(): void
    {
        if (!is_dir($this->basePath.'/routes')) {
            throw new \ErrorException('routes Dir is missing');
        }

        $files = glob($this->basePath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'*.php');

        foreach ($files as $file) {
            require_once $file;
        }
    }
}
