<?php

/**
 * @file
 * Contains \Drupal\Tests\language\Unit\LanguageNegotiationUrlTest.
 */

namespace Drupal\Tests\language\Unit {

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Tests\UnitTestCase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 * @group language
 */
class LanguageNegotiationUrlTest extends UnitTestCase {

  protected $languageManager;
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Set up some languages to be used by the language-based path processor.
    $language_de = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_de->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('de'));
    $language_en = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language_en->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $languages = array(
      'de' => $language_de,
      'en' => $language_en,
    );
    $this->languages = $languages;

    // Create a language manager stub.
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue($languages));
    $this->languageManager = $language_manager;

    // Create a user stub.
    $this->user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Test path prefix language negotiation and outbound path processing.
   *
   * @dataProvider providerTestPathPrefix
   */
  public function testPathPrefix($prefix, $prefixes, $expected_langcode) {
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($this->languages[(in_array($expected_langcode, ['en', 'de'])) ? $expected_langcode : 'en']));

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          'prefixes' => $prefixes,
        ],
      ],
    ]);

    $request = Request::create('/' . $prefix . '/foo', 'GET');
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));

    $cacheability = new CacheableMetadata();
    $options = [];
    $method->processOutbound('foo', $options, $request, $cacheability);
    $expected_cacheability = new CacheableMetadata();
    if ($expected_langcode) {
      $this->assertSame($prefix . '/', $options['prefix']);
      $expected_cacheability->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
    }
    else {
      $this->assertFalse(isset($options['prefix']));
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the path prefix test.
   *
   * @return array
   *   An array of data for checking path prefix negotiation.
   */
  public function providerTestPathPrefix() {
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => [
        'de' => 'de',
        'en-uk' => 'en',
      ],
      'expected_langcode' => 'de',
    ];
    $path_prefix_configuration[] = [
      'prefix' => 'en-uk',
      'prefixes' => [
        'de' => 'de',
        'en' => 'en-uk',
      ],
      'expected_langcode' => 'en',
    ];
    // No configuration.
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => array(),
      'expected_langcode' => FALSE,
    ];
    // Non-matching prefix.
    $path_prefix_configuration[] = [
      'prefix' => 'de',
      'prefixes' => [
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
    ];
    // Non-existing language.
    $path_prefix_configuration[] = [
      'prefix' => 'it',
      'prefixes' => [
        'it' => 'it',
        'en-uk' => 'en',
      ],
      'expected_langcode' => FALSE,
    ];
    return $path_prefix_configuration;
  }

  /**
   * Test domain language negotiation and outbound path processing.
   *
   * @dataProvider providerTestDomain
   */
  public function testDomain($http_host, $domains, $expected_langcode) {
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($this->languages['en']));

    $config = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => LanguageNegotiationUrl::CONFIG_DOMAIN,
          'domains' => $domains,
        ],
      ],
    ]);

    $request = Request::create('', 'GET', array(), array(), array(), array('HTTP_HOST' => $http_host));
    $method = new LanguageNegotiationUrl();
    $method->setLanguageManager($this->languageManager);
    $method->setConfig($config);
    $method->setCurrentUser($this->user);
    $this->assertEquals($expected_langcode, $method->getLangcode($request));

    $cacheability = new CacheableMetadata();
    $options = [];
    $this->assertSame('foo', $method->processOutbound('foo', $options, $request, $cacheability));
    $expected_cacheability = new CacheableMetadata();
    if ($expected_langcode !== FALSE && count($domains) > 1) {
      $expected_cacheability->setCacheMaxAge(Cache::PERMANENT)->setCacheContexts(['languages:' . LanguageInterface::TYPE_URL, 'url.site']);
    }
    $this->assertEquals($expected_cacheability, $cacheability);
  }

  /**
   * Provides data for the domain test.
   *
   * @return array
   *   An array of data for checking domain negotiation.
   */
  public function providerTestDomain() {

    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(
        'de' => 'http://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // No configuration.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(),
      'expected_langcode' => FALSE,
    );
    // HTTP host with a port.
    $domain_configuration[] = array(
      'http_host' => 'example.de:8080',
      'domains' => array(
        'de' => 'http://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // Domain configuration with https://.
    $domain_configuration[] = array(
      'http_host' => 'example.de',
      'domains' => array(
        'de' => 'https://example.de',
      ),
      'expected_langcode' => 'de',
    );
    // Non-matching HTTP host.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.com',
      ),
      'expected_langcode' => 'de',
    );
    // Testing a non-existing language.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'it' => 'http://example.it',
      ),
      'expected_langcode' => FALSE,
    );
    // Multiple domain configurations.
    $domain_configuration[] = array(
      'http_host' => 'example.com',
      'domains' => array(
        'de' => 'http://example.de',
        'en' => 'http://example.com',
      ),
      'expected_langcode' => 'en',
    );
    return $domain_configuration;
  }
}

}

// @todo Remove as part of https://www.drupal.org/node/2481833.
namespace {
  if (!function_exists('base_path')) {
    function base_path() {
      return '/';
    }
  }
}
