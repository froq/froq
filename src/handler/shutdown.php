<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *     <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *     <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Froq\App;

/**
 * Shutdown handler.
 * @return callable
 */
return function() {
    $error = error_get_last();
    if (isset($error['type']) && $error['type'] == E_ERROR) {
        $error = sprintf('Shutdown! E_ERROR in %s:%s ecode[%s] emesg[%s]',
            $error['file'], $error['line'], $error['type'], $error['message']);

        // works only for App
        if (isset($this) && $this instanceof App) {
            // log error first
            $this->logger->logFail($error);

            // handle response properly
            $this->response->setStatus(500)->send();
        }

        // reset error display option (@see exception handler)
        $opt = get_global('app.displayErrors');
        if ($opt !== null) {
            ini_set('display_errors', strval($opt));
        }
    }
};
