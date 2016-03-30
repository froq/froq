<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *   <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *   <http://www.gnu.org/licenses/gpl-3.0.txt>
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

/*** HTML function module. ***/

/**
 * Encode HTML input.
 * @param  string|array $input
 * @return string|array
 */
function html_encode($input)
{
   if (is_array($input)) {
      return array_map(__function__, $input);
   }

   $input = _trim($input);
   if ($input) {
      $input = str_replace(
         ["'"    , '"'    , '\\'   , '<'   , '>'],
         ['&#39;', '&#34;', '&#92;', '&lt;', '&gt;'],
         $input
      );
   }

   return $input;
}

/**
 * Decode HTML input.
 * @param  string|array $input
 * @return string|array
 */
function html_decode($input)
{
   if (is_array($input)) {
      return array_map(__function__, $input);
   }

   $input = _trim($input);
   if ($input) {
      $input = str_ireplace(
         ['&#39;', '&#34;', '&#92;', '&lt;', '&gt;'],
         ["'"    , '"'    , '\\'   , '<'   , '>'],
         $input
      );
   }

   return $input;
}

/**
 * Strip HTML tags.
 * @param  string|array $input
 * @param  bool   $decode
 * @return string|array
 */
function html_strip($input, bool $decode = false)
{
   if (is_array($input)) {
      return array_map(__function__, $input);
   }

   if ($decode) {
      $input = html_decode($input);
   }

   return strip_tags("{$input}");
}

/**
 * Remove HTML tags.
 * @param  string|array $input
 * @param  bool         $decode
 * @return string|array
 */
function html_remove($input = null, bool $decode = false)
{
   if (is_array($input)) {
      return array_map(__function__, $input);
   }

   if ($decode) {
      $input = html_decode($input);
   }

   return preg_replace('~<([^>]+)>(.*?)</([^>]+)>|<([^>]+)/?>~', '', $input);
}

/**
 * Select options.
 * @param  mixed  $input
 * @param  mixed  $current
 * @param  mixed  $extra
 * @param  array  $pairs
 * @return string
 */
function html_options($input, $current = null, $extra = null, array $pairs = null): string
{
   // shorcuts for date-time stuff
   if (is_string($input)) {
      switch ($input) {
         case 'day':
         case 'days':
            $input = [];
            for ($i = 1; $i <= 31; $i++) {
               $input[$i] = $i;
            }
            break;
         case 'month':
         case 'months':
            $input = [];
            for ($i = 1; $i <= 12; $i++) {
               $input[$i] = strftime('%B', strtotime('December +'. $i .' months'));
            }
            break;
         case 'year':
         case 'years':
            if (is_array($extra)) {
               @list($start, $stop) = $extra;
               if (!$stop)   $stop  = date('Y') + 1;
               $extra = '';
            } else {
               $start = date('Y');
               $stop  = date('Y') + 1;
            }
            $input = [];
            for ($i = $start; $i <= $stop; $i++) {
               $input[$i] = $i;
            }
            break;
         case 'hour':
         case 'hours':
            $input = [];
            for ($i = 0; $i < 24; $i++) {
               if ($extra === true) {
                  $value = sprintf('%02d:00', $i);
               } else {
                  $value = sprintf('%02d', $i);
               }
               $input[$value] = $value;
            }
            break;
         case 'minute':
         case 'minutes':
            $input = [];
            for ($i = 0; $i < 60; $i++) {
               $value = sprintf('%02d', $i);
               $input[$value] = $value;
            }
            break;
      }
   } elseif (is_array($input) && !empty($pairs)) {
      // only two dimentions like "id => 1, name => foo"
      list($key, $value) = $pairs;
      $tmp = [];
      foreach ($input as $input) {
         $input = (array) $input;
         $tmp[$input[$key]] = $input[$value];
      }
      $input = $tmp;
   }

   // check input
   if (!is_array($input)) {
      trigger_error(__function__ .'() cannot iterate given input.');
   }

   if (is_string($extra) && $extra != '') {
      $extra = ' '. trim($extra);
   } else {
      $extra = '';
   }

   $return = '';
   foreach ($input as $key => $value) {
      $return .= sprintf('<option value="%s"%s%s>%s</option>',
         $key, html_selected($key, $current), $extra, $value);
   }

   return $return;
}

/**
 * Input checked.
 * @param  mixed $a (real value)
 * @param  mixed $b
 * @param  bool  $strict
 * @return string
 */
function html_checked($a, $b, bool $strict = false): string
{
   if ($a !== null) {
      return !$strict
         ? ($a ==  $b ? ' checked' : '')
         : ($a === $b ? ' checked' : '');
   }

   return '';
}

/**
 * Input disabled.
 * @param  mixed $a (real value)
 * @param  mixed $b
 * @param  bool  $strict
 * @return string
 */
function html_disabled($a, $b, bool $strict = false): string
{
   if ($a !== null) {
      return !$strict
         ? ($a ==  $b ? ' disabled' : '')
         : ($a === $b ? ' disabled' : '');
   }

   return '';
}

/**
 * Option selected.
 * @param  mixed $a (real value)
 * @param  mixed $b
 * @param  bool  $strict
 * @return string
 */
function html_selected($a, $b, bool $strict = false): string
{
   if ($a !== null) {
      return !$strict
         ? ($a ==  $b ? ' selected' : '')
         : ($a === $b ? ' selected' : '');
   }

   return '';
}

/**
 * Compress HTML content.
 * @param  string $input
 * @return string
 */
function html_compress(string $input = null): string
{
   if ($input === null) {
      return '';
   }

   // scripts
   $input = preg_replace_callback('~(<script>(.*?)</script>)~sm', function($match) {
      $input = trim($match[2]);
      // line comments (protect http:// etc)
      if (is_local()) {
         $input = preg_replace('~(^|[^:])//([^\r\n]+)$~sm', '', $input);
      } else {
         $input = preg_replace('~(^|[^:])//.*?[\r\n]$~sm', '', $input);
      }

      // doc comments
      preg_match_all('~\s*/[\*]+(?:.*?)[\*]/\s*~sm', $input, $matchAll);
      foreach ($matchAll as $key => $value) {
         $input = str_replace($value, "\n\n", $input);
      }

      return sprintf('<script>%s</script>', trim($input));
   }, $input);

   // nbsp's
   // $input = preg_replace('~(&nbsp;)~', ' ', $input); // ones
   // $input = preg_replace('~(&nbsp;)(?=(?:&nbsp;))~', ' ', $input); // repeats

   // remove comments
   $input = preg_replace('~<!--[^-]\s*(.*?)\s*[^-]-->~sm', '', $input);
   // remove tabs
   $input = preg_replace('~^[\t ]+~sm', '', $input);
   // remove tag spaces
   $input = preg_replace('~>\s+<(/?)([\w\d-]+)~sm', '><\\1\\2', $input);

   // textarea \n problem
   $textareaTpl = '%{{{TEXTAREA}}}';
   $textareaCount = preg_match_all(
      '~(<textarea(.*?)>(.*?)</textarea>)~sm', $input, $matchAll);

   // fix textareas
   if ($textareaCount) {
      foreach ($matchAll[0] as $match) {
         $input = str_replace($match, $textareaTpl, $input);
      }
   }

   // reduce white spaces
   $input = preg_replace('~\s+~', ' ', $input);

   // fix textareas
   if ($textareaCount) {
      foreach ($matchAll[0] as $match) {
         $input = preg_replace("~{$textareaTpl}~", $match, $input, 1);
      }
   }

   return trim($input);
}
