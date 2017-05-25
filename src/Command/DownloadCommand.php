<?php

namespace Drupal\ludwig\Command;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DownloadCommand.
 *
 * @package Drupal\ludwig
 *
 * @DrupalCommand(
 *   extension="ludwig",
 *   extensionType="module"
 * )
 */
class DownloadCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('ludwig:download')
      ->setDescription($this->trans('commands.ludwig.download.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    /** @var \Drupal\ludwig\PackageManagerInterface $package_manager */
    $package_manager = $this->get('ludwig.package_manager');
    $packages = $package_manager->getPackages();
    /** @var \Drupal\ludwig\PackageDownloader $package_downloader */
    $package_downloader = $this->get('ludwig.package_downloader');

    foreach ($packages as $name => $package) {
      if (!empty($package['installed'])) {
        $io->success(sprintf('The package "%s" is already installed.', $name));
        continue;
      }
      if (empty($package['download_url'])) {
        $io->error(sprintf('No download_url was provided for package "%s".', $name));
        continue;
      }

      try {
        $package_downloader->download($package);
        $io->success(sprintf('Downloaded package "%s".', $name));
      }
      catch (FileTransferException $e) {
        $io->error(new TranslatableMarkup($e->getMessage(), $e->arguments));
      }
      catch (\Exception $e) {
        $io->error($e->getMessage());
      }

    }
  }

}
