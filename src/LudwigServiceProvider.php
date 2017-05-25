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
    $root = $container->get('app.root');
    $package_manager = new PackageManager($root);
    $extensions = $container->getParameter('container.modules');
    $namespaces = $container->getParameter('container.namespaces');
    foreach ($package_manager->getPackages() as $package_name => $package) {
      // Packages should not be added until their provider is installed.
      if ($package['installed'] && isset($extensions[$package['provider']])) {
        $namespaces[$package['namespace']] = $package['path'] . '/' . $package['src_dir'];
      }
    }
    $container->setParameter('container.namespaces', $namespaces);
  }


}
