<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 <https://opensource.org/licenses/apache-2.0>
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ViewException, Controller};

/**
 * View.
 *
 * Represents a view entity which is a part of MVC stack.
 *
 * @package froq\mvc
 * @object  froq\mvc\View
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   4.0
 */
final class View
{
    /**
     * Controller.
     * @var froq\mvc\Controller
     */
    private Controller $controller;

    /**
     * Layout.
     * @var string
     */
    private string $layout;

    /**
     * Data.
     * @var array<string, any>
     */
    private array $data;

    /**
     * Constructor.
     *
     * @param froq\mvc\Controller $controller
     */
    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the controller property.
     *
     * @return froq\mvc\Controller
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * Sets the layout property, that will be used as final output file.
     *
     * @param  string $layout
     * @return void
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Gets the layout property.
     *
     * @return string
     * @throws froq\mvc\ViewException
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Sets a data entry with given key.
     *
     * @param  string $key
     * @param  any    $value
     * @return void
     */
    public function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Gets a data entry with given key, returns `$default` value if found no entry.
     *
     * @param  string   $key
     * @param  any|null $default
     * @return any
     */
    public function getData(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Renders a given view file instantly with given data set and returns the rendered
     * contents. Throws `ViewException` if given file or layout file not found.
     *
     * @param  string                  $file
     * @param  array<string, any>|null $fileData
     * @return string
     */
    public function render(string $file, array $fileData = null): string
    {
        $file       = $this->prepareFile($file);
        $fileLayout = $this->layout ?? '';

        if (!is_file($file)) {
            throw new ViewException("View file '%s' is not exist", $file);
        }
        if (!is_file($fileLayout)) {
            throw new ViewException("View layout file '%s' is not exist", $fileLayout);
        }

        $fileData ??= [];
        foreach ($fileData as $key => $value) {
            $this->setData($key, $value);
        }

        $content = $this->renderFile($file, $fileData);
        $content = $this->renderFile($fileLayout, ['CONTENT' => $content]);

        return $content;
    }

    /**
     * Wraps the render operation in an output buffer that run by `render()` method extracting
     * `$fileData` argument if not empty and returns the rendered file's contents.
     *
     * @param  string             $file
     * @param  array<string, any> $fileData
     * @return string
     */
    private function renderFile(string $file, array $fileData): string
    {
        // Extract file data & make items accessible in included file.
        if ($fileData) {
            extract($fileData);
        }

        // Not needed anymore.
        unset($fileData);

        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    /**
     * Prepares the given file for inclusion with a fully qualified path.
     *
     * @param  string $file
     * @return string
     */
    private function prepareFile(string $file): string
    {
        if (strsfx($file, '.php')) {
            $file = substr($file, 0, -4);
        }

        return sprintf('%s/app/system/%s/view/%s.php',
            APP_DIR, $this->controller->getShortName(), $file);
    }
}
