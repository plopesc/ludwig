<?php

namespace Drupal\ludwig;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\FileTransfer\Local;

/**
 * Download packages defined in ludwig.json files.
 */
class PackageDownloader implements PackageDownloaderInterface {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new PackageDownloader object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   */
  public function __construct(FileSystemInterface $file_system, ModuleHandlerInterface $moduler_handler, $root, $site_path) {
    $moduler_handler->loadInclude('update', 'inc', 'update.manager');
    $this->fileSystem = $file_system;
    $this->root = $root;
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public function download(array $package) {
    $local_cache = update_manager_file_get($package['download_url']);
    if (!$local_cache) {
      throw new \Exception(sprintf("Unable to retrieve %s from %s.", $package['name'], $package['download_url']));
    }

    $directory = _update_manager_extract_directory();
    /** @var \Drupal\Core\Archiver\ArchiverInterface $archive */
    $archive = update_manager_archive_extract($local_cache, $directory);
    $files = $archive->listContents();
    if (!$files) {
      throw new \Exception(sprintf('The archive downloaded from %s contains no files.', $package['download_url']));
    }

    // The real path the first directory in the extracted archive.
    // @todo Will this work for non-GitHub archives?
    $source_location = $this->fileSystem->realpath($directory . '/' . $files[0]);
    $package_destination = $this->root . '/' . $package['path'];
    if (fileowner($source_location) == fileowner($this->sitePath)) {
      $file_transfer = new Local($this->root);
      $file_transfer->copyDirectory($source_location, $package_destination);
      $new_perms = substr(sprintf('%o', fileperms($package_destination)), -4, -1) . "5";
      $file_transfer->chmod($package_destination, intval($new_perms, 8), TRUE);
    }
    else {
      throw new \Exception('Cannot move package to destination.');
    }
  }

}
