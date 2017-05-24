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
    foreach ($modules as $module_name => $data) {
      $module_path = strstr($data['pathname'], $module_name, TRUE) . $module_name;
      $config = $this->jsonRead($root . '/' . $module_path . '/ludwig.json');
      $config += [
        'require' => [],
      ];

      foreach ($config['require'] as $package_name => $package_data) {
        $namespace = '';
        $src_dir = '';
        $package_path = $module_path . '/lib/' . str_replace('/', '-', $package_name) . '/' . $package_data['version'];
        $package = $this->jsonRead($root . '/' . $package_path . '/composer.json');
        $homepage = !empty($package['homepage']) ? $package['homepage'] : '';
        $description = !empty($package['description']) ? $package['description'] : '';
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
          'homepage' => $homepage,
          'description' => $description,
          'module' => $module_name,
          'download_url' => $package_data['url'],
          'path' => $package_path,
          'namespace' => $namespace,
          'src_dir' => $src_dir,
          'installed' => !empty($namespace) && !empty($src_dir),
        ];
      }
    }

    return $packages;
  }

  /**
   * Reads and decodes a json file into an array.
   *
   * @param string $filename
   *   Name of the file to read.
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
