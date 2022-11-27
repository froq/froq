<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq
 */
declare(strict_types=1);

namespace froq\app;

/**
 * View class, for templating purposes.
 *
 * @package froq\app
 * @object  froq\app\View
 * @author  Kerem Güneş
 * @since   4.0, 6.0
 */
final class View
{
    /** @var froq\app\Controller */
    public readonly Controller $controller;

    /** @var string */
    private string $layout;

    /** @var array */
    private array $data;

    /**
     * Constructor.
     *
     * @param froq\app\Controller $controller
     */
    public function __construct(Controller $controller)
    {
        $this->controller = $controller;

        // Store this view (as last view).
        $this->controller->app::registry()::set('@view', $this, false);
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
     * @param  string     $key
     * @param  mixed|null $default
     * @return mixed
     */
    public function getData(string $key, mixed $default = null): mixed
    {
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
        $content = $this->renderFile($fileLayout, ['CONTENT' => $content]);

        return $content;
    }

    /**
     * Wrap the render operation in an output buffer that run by `render()` method extracting
     * `$fileData` argument if not empty and return the rendered file's contents.
     */
    private function renderFile(string $file, array $fileData): string
    {
        // As specials.
        $FILE      = $file;
        $FILE_DATA = $fileData;

        // Not needed anymore.
        unset($file, $fileData);

        // Extract file data, make items accessible in included file.
        $FILE_DATA && extract($FILE_DATA);

        ob_start();
        include $FILE;
        return ob_get_clean();
    }

    /**
     * Prepare the given file for inclusion with a fully qualified path.
     */
    private function prepareFile(string $file): string
    {
        if (str_ends_with($file, '.php')) {
            $file = substr($file, 0, -4);
        }

        // Must be defined as full path.
        $viewBase = $this->controller->app->config('view.base');
        if ($viewBase) {
            return sprintf('%s/%s.php', $viewBase, $file);
        }

        return sprintf('%s/app/system/%s/view/%s.php', APP_DIR, $this->controller->getShortName(), $file);
    }
}
