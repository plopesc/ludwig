<?php

namespace Drupal\ludwig;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides information about ludwig-managed packages.
 *
 * Modules can define a ludwig.json which is discovered by this class.
 *
 * The container is used instead of the ModuleHandler to allow the
 * class to be used within a service provider.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new PackageManager object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    $packages = [];
    $root = $this->container->get('app.root');
    $modules = $this->container->getParameter('container.modules');
    foreach ($modules as $module => $data) {
      $module_path = strstr($data['pathname'], $module, TRUE) . $module;
      $config_path = $root . '/' . $module_path . '/ludwig.json';
      $config = $this->jsonRead($config_path);
      if (!isset($config['require'])) {
        $config['require'] = [];
      }
      foreach ($config['require'] as $package_name => $package_data) {
        $namespace = '';
        $src_dir = '';
        $package_path = $module_path . '/lib/' . str_replace('/', '-', $package_name) . '/' . $package_data['version'];
        $package = $this->jsonRead($root . '/' . $package_path . '/composer.json');
        if (!empty($package['autoload'])) {
          $autoload = reset($package['autoload']);
          $package_namespaces = array_keys($autoload);
          $namespace = reset($package_namespaces);
          $src_dir = $autoload[$namespace];
          // The namespace must not have the leading backslash.
          if (substr($namespace, -1, 1) == '\\') {
            $namespace = substr($namespace, 0, -1);
          }
        }
        $packages[$package_name] = [
          'version' => $package_data['version'],
          'url' => $package_data['url'],
          'namespace' => $namespace,
          'path' => $package_path,
          'src_dir' => $src_dir,
          'found' => !empty($namespace) && !empty($src_dir),
        ];
      }
    }

    return $packages;
  }

  /**
   * Reads and decodes a json file into an array.
   *
   * @return array
   *   The decoded json data.
   */
  protected function jsonRead($filename) {
    $data = [];
    if (file_exists($filename)) {
      $data = file_get_contents($filename);
      $data = json_decode($data, TRUE);
      if (!$data) {
        $data = [];
      }
    }

    return $data;
  }


}
