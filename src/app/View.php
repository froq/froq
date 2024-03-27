<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
namespace froq\app;

use State;

/**
 * View class, for templating works.
 *
 * @package froq\app
 * @class   froq\app\View
 * @author  Kerem Güneş
 * @since   4.0, 6.0
 */
class View
{
    /** Controller instance. */
    public readonly Controller|null $controller;

    /** Dynamic state reference. */
    public readonly State $state;

    /** View file. */
    private string $layout;

    /** View data. */
    private array $data;

    /**
     * Constructor.
     *
     * @param froq\app\Controller|null $controller
     */
    public function __construct(Controller $controller = null)
    {
        $this->controller = $controller;
        $this->state      = new State();

        // Store this view (as last view).
        $this->controller?->app::registry()::setView($this, false);
    }

    /**
     * Set layout property, that will be used as final output file.
     *
     * @param  string $layout
     * @return self
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get layout.
     *
     * @return string|null
     */
    public function getLayout(): string|null
    {
        return $this->layout ?? null;
    }

    /**
     * Set a data entry with given key.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    public function setData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a data entry with given key, return `$default` value if found no entry.
     *
     * @param  string|null $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function getData(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data ?? $default;
        }

        return $this->data[$key] ?? $default;
    }

    /**
     * Render a given view file instantly with given data set and return the rendered contents,
     * throw `ViewException` if given file or layout file not exists.
     *
     * @param  string     $file
     * @param  array|null $fileData
     * @return string
     */
    public function render(string $file, array $fileData = null): string
    {
        $file = $this->prepareFile($file);
        if (!is_file($file)) {
            throw new ViewException('View file %q not found', $file);
        }

        $fileLayout = $this->getLayout();
        if (!is_file($fileLayout)) {
            throw new ViewException('View layout file %q not found', $fileLayout);
        }

        $fileData ??= [];
        foreach ($fileData as $key => $value) {
            $this->setData($key, $value);
        }

        // Render file first, then send its contents to layout file.
        $content = $this->renderFile($file, $fileData);
        $content = $this->renderFile($fileLayout, ['CONTENT' => $content] + $fileData);

        return $content;
    }

    /**
     * Wrap the render operation in an output buffer that run by `render()` method extracting
     * `$fileData` argument if not empty and return the rendered file's contents.
     *
     * @param  string     $file
     * @param  array|null $fileData
     * @return string
     */
    public function renderFile(string $file, array $fileData = null): string
    {
        // As specials.
        $FILE      = $file;
        $FILE_DATA = $fileData;

        // Not needed anymore.
        unset($file, $fileData);

        // Extract file data, make items accessible in included file.
        $FILE_DATA && extract($FILE_DATA);

        ob_start(); require $FILE; return ob_get_clean();
    }

    /**
     * Prepare the given file for inclusion with a fully qualified path.
     *
     * @param  string $file
     * @param  bool   $php
     * @return string
     */
    public function prepareFile(string $file, bool $php = true): string
    {
        if ($php && strsfx($file, '.php')) {
            $file = strsub($file, 0, -4);
        }

        if (!$this->controller) {
            return get_real_path($file);
        }

        // Must be defined as full path.
        $viewBase = $this->controller->app->config('view.base');
        if ($viewBase) {
            return sprintf('%s/%s.php', $viewBase, $file);
        }

        return sprintf('%s/app/system/%s/view/%s.php',
            APP_DIR, $this->controller->getShortName(), $file);
    }
}
