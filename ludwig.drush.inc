<?php

use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @file
 * Contains Drush integration for Ludwig.
 */

/**
 * Implements hook_drush_log().
 */
function ludwig_drush_command() {
  return [
    'ludwig-download' => [
      'description' => "Downloads packages listed in ludwig.json files.",
    ],
  ];
}

/**
 * Implements drush_HOOK().
 */
function drush_ludwig_download() {
  /** @var \Drupal\ludwig\PackageManagerInterface $package_manager */
  $package_manager = \Drupal::service('ludwig.package_manager');
  $packages = $package_manager->getPackages();
  /** @var \Drupal\ludwig\PackageDownloader $package_downloader */
  $package_downloader = \Drupal::service('ludwig.package_downloader');

  foreach ($packages as $name => $package) {
    if (!empty($package['installed'])) {
      drush_log(sprintf('The package "%s" is already installed.', $name), 'ok');
      continue;
    }
    if (empty($package['download_url'])) {
      drush_log(sprintf('No download_url was provided for package "%s".', $name), 'error');
      continue;
    }

    try {
      $package_downloader->download($package);
      drush_log(sprintf('Downloaded package "%s".', $name), 'ok');
    }
    catch (FileTransferException $e) {
      drush_log(new TranslatableMarkup($e->getMessage(), $e->arguments), 'error');
    }
    catch (\Exception $e) {
      drush_log($e->getMessage(), 'error');
    }
  }
}

/**
 * Implements hook_drush_pm_post_download().
 */
function ludwig_drush_pm_post_download($request, $release) {
  drush_set_option('strict', FALSE);
  drush_invoke_process('@self', 'ludwig-download');
}
