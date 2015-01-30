<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\UrlTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Confirm that \Drupal\Core\Url,
 * \Drupal\Component\Utility\UrlHelper::filterQueryParameters(),
 * \Drupal\Component\Utility\UrlHelper::buildQuery(), and _l() work correctly
 * with various input.
 *
 * @group Common
 */
class UrlTest extends WebTestBase {

  public static $modules = array('common_test');

  /**
   * Confirms that invalid URLs are filtered in link generating functions.
   */
  function testLinkXSS() {
    // Test _l().
    $text = $this->randomMachineName();
    $path = "<SCRIPT>alert('XSS')</SCRIPT>";
    $link = _l($text, $path);
    $sanitized_path = check_url(Url::fromUri('base:' . $path)->toString());
    $this->assertTrue(strpos($link, $sanitized_path) !== FALSE, format_string('XSS attack @path was filtered by _l().', array('@path' => $path)));

    // Test \Drupal\Core\Url.
    $link = Url::fromUri('base:' . $path)->toString();
    $sanitized_path = check_url(Url::fromUri('base:' . $path)->toString());
    $this->assertTrue(strpos($link, $sanitized_path) !== FALSE, format_string('XSS attack @path was filtered by #theme', ['@path' => $path]));
  }

  /**
   * Tests that default and custom attributes are handled correctly on links.
   */
  function testLinkAttributes() {
    // Test that hreflang is added when a link has a known language.
    $language = new Language(array('id' => 'fr', 'name' => 'French'));
    $hreflang_link = array(
      '#type' => 'link',
      '#options' => array(
        'language' => $language,
      ),
      '#url' => Url::fromUri('http://drupal.org'),
      '#title' => 'bar',
    );
    $langcode = $language->getId();

    // Test that the default hreflang handling for links does not override a
    // hreflang attribute explicitly set in the render array.
    $hreflang_override_link = $hreflang_link;
    $hreflang_override_link['#options']['attributes']['hreflang'] = 'foo';

    $rendered = drupal_render($hreflang_link);
    $this->assertTrue($this->hasAttribute('hreflang', $rendered, $langcode), format_string('hreflang attribute with value @langcode is present on a rendered link when langcode is provided in the render array.', array('@langcode' => $langcode)));

    $rendered = drupal_render($hreflang_override_link);
    $this->assertTrue($this->hasAttribute('hreflang', $rendered, 'foo'), format_string('hreflang attribute with value @hreflang is present on a rendered link when @hreflang is provided in the render array.', array('@hreflang' => 'foo')));

    // Test the active class in links produced by _l() and #type 'link'.
    $options_no_query = array();
    $options_query = array(
      'query' => array(
        'foo' => 'bar',
        'one' => 'two',
      ),
    );
    $options_query_reverse = array(
      'query' => array(
        'one' => 'two',
        'foo' => 'bar',
      ),
    );

    // Test #type link.
    $path = 'common-test/type-link-active-class';

    $this->drupalGet($path, $options_no_query);
    $links = $this->xpath('//a[@href = :href and contains(@class, :class)]', array(':href' => Url::fromRoute('common_test.l_active_class', [], $options_no_query)->toString(), ':class' => 'active'));
    $this->assertTrue(isset($links[0]), 'A link generated by _l() to the current page is marked active.');

    $links = $this->xpath('//a[@href = :href and not(contains(@class, :class))]', array(':href' => Url::fromRoute('common_test.l_active_class', [], $options_query)->toString(), ':class' => 'active'));
    $this->assertTrue(isset($links[0]), 'A link generated by _l() to the current page with a query string when the current page has no query string is not marked active.');

    $this->drupalGet($path, $options_query);
    $links = $this->xpath('//a[@href = :href and contains(@class, :class)]', array(':href' => Url::fromRoute('common_test.l_active_class', [], $options_query)->toString(), ':class' => 'active'));
    $this->assertTrue(isset($links[0]), 'A link generated by _l() to the current page with a query string that matches the current query string is marked active.');

    $links = $this->xpath('//a[@href = :href and contains(@class, :class)]', array(':href' => Url::fromRoute('common_test.l_active_class', [], $options_query_reverse)->toString(), ':class' => 'active'));
    $this->assertTrue(isset($links[0]), 'A link generated by _l() to the current page with a query string that has matching parameters to the current query string but in a different order is marked active.');

    $links = $this->xpath('//a[@href = :href and not(contains(@class, :class))]', array(':href' => Url::fromRoute('common_test.l_active_class', [], $options_no_query)->toString(), ':class' => 'active'));
    $this->assertTrue(isset($links[0]), 'A link generated by _l() to the current page without a query string when the current page has a query string is not marked active.');

    // Test adding a custom class in links produced by _l() and #type 'link'.
    // Test _l().
    $class_l = $this->randomMachineName();
    $link_l = \Drupal::l($this->randomMachineName(), new Url('<current>', [], ['attributes' => ['class' => [$class_l]]]));
    $this->assertTrue($this->hasAttribute('class', $link_l, $class_l), format_string('Custom class @class is present on link when requested by l()', array('@class' => $class_l)));

    // Test #type.
    $class_theme = $this->randomMachineName();
    $type_link = array(
      '#type' => 'link',
      '#title' => $this->randomMachineName(),
      '#url' => Url::fromRoute('<current>'),
      '#options' => array(
        'attributes' => array(
          'class' => array($class_theme),
        ),
      ),
    );
    $link_theme = drupal_render($type_link);
    $this->assertTrue($this->hasAttribute('class', $link_theme, $class_theme), format_string('Custom class @class is present on link when requested by #type', array('@class' => $class_theme)));
  }

  /**
   * Tests that link functions support render arrays as 'text'.
   */
  function testLinkRenderArrayText() {
    // Build a link with _l() for reference.
    $l = \Drupal::l('foo', Url::fromUri('http://drupal.org'));

    // Test a renderable array passed to _l().
    $renderable_text = array('#markup' => 'foo');
    $l_renderable_text = \Drupal::l($renderable_text, Url::fromUri('http://drupal.org'));
    $this->assertEqual($l_renderable_text, $l);

    // Test a themed link with plain text 'text'.
    $type_link_plain_array = array(
      '#type' => 'link',
      '#title' => 'foo',
      '#url' => Url::fromUri('http://drupal.org'),
    );
    $type_link_plain = drupal_render($type_link_plain_array);
    $this->assertEqual($type_link_plain, $l);

    // Build a themed link with renderable 'text'.
    $type_link_nested_array = array(
      '#type' => 'link',
      '#title' => array('#markup' => 'foo'),
      '#url' => Url::fromUri('http://drupal.org'),
    );
    $type_link_nested = drupal_render($type_link_nested_array);
    $this->assertEqual($type_link_nested, $l);
  }

  /**
   * Checks for class existence in link.
   *
   * @param $link
   *   URL to search.
   * @param $class
   *   Element class to search for.
   *
   * @return bool
   *   TRUE if the class is found, FALSE otherwise.
   */
  private function hasAttribute($attribute, $link, $class) {
    return preg_match('|' . $attribute . '="([^\"\s]+\s+)*' . $class . '|', $link);
  }

  /**
   * Tests UrlHelper::filterQueryParameters().
   */
  function testDrupalGetQueryParameters() {
    $original = array(
      'a' => 1,
      'b' => array(
        'd' => 4,
        'e' => array(
          'f' => 5,
        ),
      ),
      'c' => 3,
    );

    // First-level exclusion.
    $result = $original;
    unset($result['b']);
    $this->assertEqual(UrlHelper::filterQueryParameters($original, array('b')), $result, "'b' was removed.");

    // Second-level exclusion.
    $result = $original;
    unset($result['b']['d']);
    $this->assertEqual(UrlHelper::filterQueryParameters($original, array('b[d]')), $result, "'b[d]' was removed.");

    // Third-level exclusion.
    $result = $original;
    unset($result['b']['e']['f']);
    $this->assertEqual(UrlHelper::filterQueryParameters($original, array('b[e][f]')), $result, "'b[e][f]' was removed.");

    // Multiple exclusions.
    $result = $original;
    unset($result['a'], $result['b']['e'], $result['c']);
    $this->assertEqual(UrlHelper::filterQueryParameters($original, array('a', 'b[e]', 'c')), $result, "'a', 'b[e]', 'c' were removed.");
  }

  /**
   * Tests UrlHelper::parse().
   */
  function testDrupalParseUrl() {
    // Relative, absolute, and external URLs, without/with explicit script path,
    // without/with Drupal path.
    foreach (array('', '/', 'http://drupal.org/') as $absolute) {
      foreach (array('', 'index.php/') as $script) {
        foreach (array('', 'foo/bar') as $path) {
          $url = $absolute . $script . $path . '?foo=bar&bar=baz&baz#foo';
          $expected = array(
            'path' => $absolute . $script . $path,
            'query' => array('foo' => 'bar', 'bar' => 'baz', 'baz' => ''),
            'fragment' => 'foo',
          );
          $this->assertEqual(UrlHelper::parse($url), $expected, 'URL parsed correctly.');
        }
      }
    }

    // Relative URL that is known to confuse parse_url().
    $url = 'foo/bar:1';
    $result = array(
      'path' => 'foo/bar:1',
      'query' => array(),
      'fragment' => '',
    );
    $this->assertEqual(UrlHelper::parse($url), $result, 'Relative URL parsed correctly.');

    // Test that drupal can recognize an absolute URL. Used to prevent attack vectors.
    $url = 'http://drupal.org/foo/bar?foo=bar&bar=baz&baz#foo';
    $this->assertTrue(UrlHelper::isExternal($url), 'Correctly identified an external URL.');

    // Test that UrlHelper::parse() does not allow spoofing a URL to force a malicious redirect.
    $parts = UrlHelper::parse('forged:http://cwe.mitre.org/data/definitions/601.html');
    $this->assertFalse(UrlHelper::isValid($parts['path'], TRUE), '\Drupal\Component\Utility\UrlHelper::isValid() correctly parsed a forged URL.');
  }

  /**
   * Tests external URL handling.
   */
  function testExternalUrls() {
    $test_url = 'http://drupal.org/';

    // Verify external URL can contain a fragment.
    $url = $test_url . '#drupal';
    $result = Url::fromUri($url)->toString();
    $this->assertEqual($url, $result, 'External URL with fragment works without a fragment in $options.');

    // Verify fragment can be overidden in an external URL.
    $url = $test_url . '#drupal';
    $fragment = $this->randomMachineName(10);
    $result = Url::fromUri($url, array('fragment' => $fragment))->toString();
    $this->assertEqual($test_url . '#' . $fragment, $result, 'External URL fragment is overidden with a custom fragment in $options.');

    // Verify external URL can contain a query string.
    $url = $test_url . '?drupal=awesome';
    $result = Url::fromUri($url)->toString();
    $this->assertEqual($url, $result, 'External URL with query string works without a query string in $options.');

    // Verify external URL can be extended with a query string.
    $url = $test_url;
    $query = array($this->randomMachineName(5) => $this->randomMachineName(5));
    $result = Url::fromUri($url, array('query' => $query))->toString();
    $this->assertEqual($url . '?' . http_build_query($query, '', '&'), $result, 'External URL can be extended with a query string in $options.');

    // Verify query string can be extended in an external URL.
    $url = $test_url . '?drupal=awesome';
    $query = array($this->randomMachineName(5) => $this->randomMachineName(5));
    $result = Url::fromUri($url, array('query' => $query))->toString();
    $this->assertEqual($url . '&' . http_build_query($query, '', '&'), $result, 'External URL query string can be extended with a custom query string in $options.');
  }
}
