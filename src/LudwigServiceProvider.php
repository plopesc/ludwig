<?php

namespace Drupal\ludwig;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Adds ludwig-managed packages to the autoloader.
 *
 * Service providers are only executed when the container is being built,
 * removing the need to cache the module's package information.
 */
class LudwigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $package_manager = new PackageManager($container);
    $namespaces = $container->getParameter('container.namespaces');
    $packages = $package_manager->getPackages();
    foreach ($packages as $package_name => $package) {
      if ($package['found']) {
        $namespaces[$package['namespace']] = $package['path'] . '/' . $package['src_dir'];
      }
    }
    $container->setParameter('container.namespaces', $namespaces);
  }


}
