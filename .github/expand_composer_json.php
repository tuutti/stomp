#!/usr/bin/env php
<?php
// phpcs:ignoreFile

/**
 * @file
 * Populate a composer.json with module's dependencies.
 */
[, $project_json, $module_path, $php_version] = $argv;

$json_project = read_composer_json($project_json);
$json_project['repositories'][] = [
  'type' => 'path',
  'url' => $module_path,
];
// Override default platform version.
$json_core['config']['platform']['php'] = $php_version;
$json_core = read_composer_json('composer.json');
$json_rich = merge_deep($json_project, $json_core);
// Remove empty top-level items.
$json_rich = array_filter($json_rich);

file_put_contents('composer.json', json_encode($json_rich, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));

/**
 * Deeply merges arrays. Borrowed from Drupal core.
 */
function merge_deep(): array {
  return merge_deep_array(func_get_args());
}

/**
 * Deeply merges arrays. Borrowed from drupal.org/project/core.
 *
 * @param array $arrays
 *   An array of array that will be merged.
 * @param bool $preserve_integer_keys
 *   Whether to preserve integer keys.
 */
function merge_deep_array(array $arrays, bool $preserve_integer_keys = FALSE): array {
  $result = [];
  foreach ($arrays as $array) {
    foreach ($array as $key => $value) {
      if (is_int($key) && !$preserve_integer_keys) {
        $result[] = $value;
      }
      elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
        $result[$key] = merge_deep_array([$result[$key], $value], $preserve_integer_keys);
      }
      else {
        $result[$key] = $value;
      }
    }
  }
  return $result;
}
/**
 * Converts composer.json to array.
 *
 * @param string $path
 *   The composer path.
 *
 * @return array
 *   The composer.json.
 */
function read_composer_json(string $path) : array {
  return json_decode(file_get_contents($path), TRUE);
}
