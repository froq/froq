<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\mvc;

use froq\mvc\{ViewException, Controller};

/**
 * View.
 *
 * Represents a view entity which is a part of MVC pattern.
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
     * Meta.
     * @var array<string, any>
     */
    private array $meta;

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
     * Sets a meta entry with given name.
     *
     * @param  string $name
     * @param  any    $value
     * @return void
     */
    public final function setMeta(string $name, $value): void
    {
        $this->meta[$name] = $value;
    }

    /**
     * Gets a meta entry with given name, returns `$valueDefault` value if found no entry.
     *
     * @param  string $name
     * @param  any|null $valueDefault
     * @return any
     */
    public final function getMeta(string $name, $valueDefault = null)
    {
        return $this->meta[$name] ?? $valueDefault;
    }

    /**
     * Sets a data entry with given key.
     *
     * @param  string $key
     * @param  any    $value
     * @return void
     */
    public final function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Gets a data entry with given key, returns `$valueDefault` value if found no entry.
     *
     * @param  string   $key
     * @param  any|null $valueDefault
     * @return any
     */
    public final function getData(string $key, $valueDefault = null)
    {
        return $this->data[$key] ?? $valueDefault;
    }

    /**
     * Renders a given view file instantly with given meta & data set and returns the rendered
     * contents. Throws `ViewException` if given file or layout file not found.
     *
     * @param  string                            $file
     * @param  array<string, array<string, any>> $fileMetaData
     * @return string
     */
    public function render(string $file, array $fileMetaData): string
    {
        $file       = $this->prepareFile($file);
        $fileLayout = $this->layout ?? '';

        if (!is_file($file)) {
            throw new ViewException('View file "%s" is not exists', [$file]);
        }
        if (!is_file($fileLayout)) {
            throw new ViewException('View layout file "%s" is not exists', [$fileLayout]);
        }

        $meta = (array) ($fileMetaData['meta'] ?? []);
        $data = (array) ($fileMetaData['data'] ?? []);

        foreach ($meta as $name => $value) {
            $this->setMeta($name, $value);
        }
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }

        $content = $this->renderFile($file, $data);
        $content = $this->renderFile($fileLayout, ['CONTENT' => $content]);

        return $content;
    }

    /**
     * Wraps the render operation in an output buffer that run by `render()` method extracting
     * `$data` argument if not empty and returns the rendered file's contents.
     *
     * @param  string             $file
     * @param  array<string, any> $data
     * @return string
     */
    private function renderFile(string $file, array $data): string
    {
        // Extract data & make accessible in included file.
        $data && extract($data);

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
        if (substr($file, -4) == '.php') {
            $file = substr($file, 0, -4);
        }

        return sprintf(
            '%s/app/system/%s/view/%s.php',
            APP_DIR, $this->controller->getShortName(), $file);
    }
}
