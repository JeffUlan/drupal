<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleLookup.
 */

namespace Drupal\locale;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A cache collector to allow for dynamic building of the locale cache.
 */
class LocaleLookup extends CacheCollector {

  /**
   * A language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The msgctxt context.
   *
   * @var string
   */
  protected $context;

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $stringStorage;

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a LocaleLookup object.
   *
   * @param string $langcode
   *   The language code.
   * @param string $context
   *   The string context.
   * @param \Drupal\locale\StringStorageInterface $string_storage
   *   The string storage.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($langcode, $context, StringStorageInterface $string_storage, CacheBackendInterface $cache, LockBackendInterface $lock, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->langcode = $langcode;
    $this->context = (string) $context;
    $this->stringStorage = $string_storage;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;

    $this->cache = $cache;
    $this->lock = $lock;
    $this->tags = array('locale');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      // Add the current user's role IDs to the cache key, this ensures that,
      // for example, strings for admin menu items and settings forms are not
      // cached for anonymous users.
      $user = \Drupal::currentUser();
      $rids = $user ? implode(':', array_keys($user->getRoles())) : '0';
      $this->cid = "locale:{$this->langcode}:{$this->context}:$rids";

      // Getting the roles from the current user might have resulted in t()
      // calls that attempted to get translations from the locale cache. In that
      // case they would not go into this method again as
      // CacheCollector::lazyLoadCache() already set the loaded flag. They would
      // however call resolveCacheMiss() and add that string to the list of
      // cache misses that need to be written into the cache. Prevent that by
      // resetting that list. All that happens in such a case are a few uncached
      // translation lookups.
      $this->keysToPersist = array();
    }
    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($offset) {
    $translation = $this->stringStorage->findTranslation(array(
      'language' => $this->langcode,
      'source' => $offset,
      'context' => $this->context,
    ));

    if ($translation) {
      $value = !empty($translation->translation) ? $translation->translation : TRUE;
    }
    else {
      // We don't have the source string, update the {locales_source} table to
      // indicate the string is not translated.
      $this->stringStorage->createString(array(
        'source' => $offset,
        'context' => $this->context,
        'version' => \Drupal::VERSION,
      ))->addLocation('path', $this->requestUri())->save();
      $value = TRUE;
    }

    // If there is no translation available for the current language then use
    // language fallback to try other translations.
    if ($value === TRUE) {
      $fallbacks = $this->languageManager->getFallbackCandidates(array('langcode' => $this->langcode, 'operation' => 'locale_lookup', 'data' => $offset));
      if (!empty($fallbacks)) {
        foreach ($fallbacks as $langcode) {
          $translation = $this->stringStorage->findTranslation(array(
            'language' => $langcode,
            'source' => $offset,
            'context' => $this->context,
          ));

          if ($translation && !empty($translation->translation)) {
            $value = $translation->translation;
            break;
          }
        }
      }
    }

    $this->storage[$offset] = $value;
    // Disabling the usage of string caching allows a module to watch for
    // the exact list of strings used on a page. From a performance
    // perspective that is a really bad idea, so we have no user
    // interface for this. Be careful when turning this option off!
    if ($this->configFactory->get('locale.settings')->get('cache_strings')) {
      $this->persist($offset);
    }
    return $value;
  }

  /**
   * Wraps request_uri().
   */
  protected function requestUri($omit_query_string = FALSE) {
    return request_uri($omit_query_string);
  }

}
