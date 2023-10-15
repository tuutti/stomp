#!/usr/bin/env php
<?php
// phpcs:ignoreFile

/**
 * @file
 * Populate a composer.json with the right drupal/core-recommended and other dependencies.
 */
[, $path, $target_core] = $argv;

if (empty($path) || empty($target_core)) {
  throw new RuntimeException('Unable to determine project path or target core.');
}

$json_project = json_decode(file_get_contents('composer.json'), TRUE);
$json_default = default_json($path, $target_core);

// Avoid adding core-recommended if drupal/core is present.
if (isset($json_project['require-dev']['drupal/core'])) {
  unset($json_default['require-dev']['drupal/core-recommended']);
}
// Conditionally add prophecy.
if (!isset($json_project['require-dev']['phpspec/prophecy-phpunit']) && version_compare($target_core, '9.0.0', '>=')) {
  $json_default['require-dev']['phpspec/prophecy-phpunit'] = '^2';
}
$json_rich = merge_deep($json_default, $json_project);
// Remove empty top-level items.
$json_rich = array_filter($json_rich);
$json_rich['name'] = 'drupal/test-runner';
$json_rich['type'] = 'project';

file_put_contents('composer.json', json_encode($json_rich, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));

/**
 * Get default composer.json contents.
 */
function default_json(string $path, $drupalConstraint): array {
  $webRoot = getenv('_WEB_ROOT') ?: 'web';
  return [
    'name' => '',
    'type' => '',
    'description' => 'A description',
    'license' => 'GPL-2.0-or-later',
    'repositories' => [
      [
        'type' => 'path',
        'url' => $path,
      ],
      [
        'type' => 'composer',
        'url' => 'https://packages.drupal.org/8',
      ],
    ],
    'require' => [],
    'require-dev' => [
      'composer/installers' => '^1 || ^2',
      'drupal/core-composer-scaffold' => $drupalConstraint,
      'cweagans/composer-patches' => '~1.0',
      'drupal/core-recommended' => $drupalConstraint,
      'drupal/core-dev' => $drupalConstraint,
      'drush/drush' => '^10 || ^11 || ^12',
      'php-parallel-lint/php-parallel-lint' => '^1.2',
    ],
    'minimum-stability' => 'dev',
    'prefer-stable' => TRUE,
    'config' => [
      'process-timeout' => 36000,
      "allow-plugins" => [
        "dealerdirect/phpcodesniffer-composer-installer" => TRUE,
        "composer/installers" => TRUE,
        "cweagans/composer-patches" => TRUE,
        "drupal/core-composer-scaffold" => TRUE,
        "drupalspoons/composer-plugin" => TRUE,
        "phpstan/extension-installer" => TRUE,
      ],
    ],
    'extra' => [
      'installer-paths' => [
        $webRoot . '/core' => [
          0 => 'type:drupal-core',
        ],
        $webRoot . '/libraries/{$name}' => [
          0 => 'type:drupal-library',
        ],
        $webRoot . '/modules/contrib/{$name}' => [
          0 => 'type:drupal-module',
        ],
        $webRoot . '/profiles/{$name}' => [
          0 => 'type:drupal-profile',
        ],
        $webRoot . '/themes/{$name}' => [
          0 => 'type:drupal-theme',
        ],
        'drush/{$name}' => [
          0 => 'type:drupal-drush',
        ],
      ],
      'drupal-scaffold' => [
        'locations' => [
          'web-root' => $webRoot . '/',
        ],
      ],
      'drush' => [
        'services' => [
          'drush.services.yml' => '^9 || ^10 || ^11',
        ],
      ],
    ],
  ];
}

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
