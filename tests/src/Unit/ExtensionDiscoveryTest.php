<?php

namespace Drupal\Tests\ludwig\Unit;

use Drupal\ludwig\ExtensionDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\ludwig\ExtensionDiscovery
 * @group ludwig
 */
class ExtensionDiscoveryTest extends UnitTestCase {

  /**
   * @var \Drupal\ludwig\ExtensionDiscovery
   */
  protected $discovery;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Simulate modules in multiple sites and multiple profiles.
    $structure = [
      'modules' => [
        'test1' => $this->generateModule('test1'),
      ],
      'profiles' => [
        'commons' => [
          'commons.info.yml' => 'type: profile',
          'commons.profile' => '<?php',
          'modules' => [
            'test2' => $this->generateModule('test2'),
          ],
        ],
      ],
      'sites' => [
        'all' => [
          'modules' => [
            'test3' => $this->generateModule('test3'),
          ],
        ],
        'default' => [
          'modules' => [
            'test4' => $this->generateModule('test4'),
          ],
        ],
        'test.site.com' => [
          'profiles' => [
            'lightning' => [
              'lightning.info.yml' => 'type: profile',
              'lightning.profile' => '<?php',
              'modules' => [
                'test5' => $this->generateModule('test5'),
              ],
            ],
          ],
          'modules' => [
            'test6' => $this->generateModule('test6'),
          ],
        ],
      ],
    ];
    vfsStream::setup('drupal', null, $structure);

    $this->discovery = new ExtensionDiscovery('vfs://drupal');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->discovery->resetCache();
  }

  /**
   * @covers ::scan
   * @covers ::getSiteDirectories
   */
  public function testScan() {
    $expected_profiles = [
      'commons', 'lightning',
    ];
    $profiles = $this->discovery->scan('profile');
    $this->assertEquals($expected_profiles, array_keys($profiles));

    $expected_extensions = [
      'test2', 'test5', 'test3', 'test1', 'test4', 'test6',
    ];
    $profile_directories = array_map(function ($profile) {
      return $profile->getPath();
    }, $profiles);
    $this->discovery->setProfileDirectories($profile_directories);
    $extensions = $this->discovery->scan('module');
    $this->assertEquals($expected_extensions, array_keys($extensions));
  }

  /**
   * Returns the file structure for a module.
   */
  protected function generateModule($name) {
    return [
      $name . '.module' => '<?php',
      $name . '.info.yml' => 'type: module',
    ];
  }

}
