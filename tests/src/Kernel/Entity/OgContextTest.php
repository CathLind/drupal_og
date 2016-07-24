<?php

namespace Drupal\Tests\og\Kernel\Entity;

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\OgContextTest.
 */

use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgContextHandlerInterface;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Test OG context plugins.
 *
 * @see og_entity_create_access().
 *
 * @group og
 */
class OgContextTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig(['og']);

    Og::contextHandler()->updateConfigStorage();
  }

  /**
   * Test that OgContext handler will return a list according to a given logic.
   */
  public function testOgContextPluginsList() {
    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ALL);

    // Check we got the plugins we know that exists.
    if (empty($plugins['current_user']) || empty($plugins['entity'])) {
      $this->fail('The expected plugins, Current user and Entity, was not found in the plugins list');
    }

    // Get all the plugins in the storage which have a matching storage.
    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ONLY_IN_STORAGE);

    if (empty($plugins['current_user']) || empty($plugins['entity'])) {
      $this->fail('The expected plugins, Current user and Entity, was not found in the plugins list');
    }

    // Get all active plugins.
    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ONLY_ACTIVE);

    $this->assertEmpty($plugins);

    // Enable a plugin.
    Og::contextHandler()->updatePlugin('entity', ['status' => 1]);

    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ONLY_ACTIVE);
    $this->assertArrayHasKey('entity', $plugins);
    $this->assertArrayNotHasKey('current_user', $plugins);

    // Enable the second plugin and change the weight.
    Og::contextHandler()->updatePlugin('entity', ['weight' => 1]);
    Og::contextHandler()->updatePlugin('current_user', ['weight' => 0, 'status' => 1]);

    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ONLY_ACTIVE);
    $this->assertArrayHasKey('entity', $plugins);
    $this->assertArrayHasKey('current_user', $plugins);

    $get_plugins_location = function ($plugins) {
      return [
        array_search('entity', $plugins),
        array_search('current_user', $plugins),
      ];
    };

    list($entity_position, $current_user_position) = $get_plugins_location(array_keys($plugins));

    $this->assertTrue($entity_position > $current_user_position);

    // Change the weight.
    Og::contextHandler()->updatePlugin('entity', ['weight' => -1]);

    $plugins = Og::contextHandler()->getPlugins(OgContextHandlerInterface::RETURN_ONLY_ACTIVE);

    list($entity_position, $current_user_position) = $get_plugins_location(array_keys($plugins));
    $this->assertTrue($current_user_position > $entity_position);
  }

}
