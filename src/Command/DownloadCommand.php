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
    /** @var \Drupal\ludwig\PackageDownloader $package_downloader */
    $package_downloader = $this->get('ludwig.package_downloader');

    $packages = array_filter($package_manager->getPackages(), function ($package) {
      return empty($package['installed']);
    });
    foreach ($packages as $name => $package) {
      if (empty($package['download_url'])) {
        $io->error(sprintf($this->trans('commands.ludwig.download.errors.no-download-url'), $name));
        continue;
      }

      try {
        $package_downloader->download($package);
        $io->success(sprintf($this->trans('commands.ludwig.download.messages.success'), $name));
      }
      catch (FileTransferException $e) {
        $io->error(new TranslatableMarkup($e->getMessage(), $e->arguments));
      }
      catch (\Exception $e) {
        $io->error($e->getMessage());
      }
    }

    if (empty($packages)) {
      $io->success($this->trans('commands.ludwig.download.messages.no-download'));
    }
  }

}
