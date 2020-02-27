<?php

namespace Drupal\Tests\Core\DrupalKernel {

  use Drupal\Core\DrupalKernel;
  use Drupal\Tests\UnitTestCase;
  use org\bovigo\vfs\vfsStream;
  use Symfony\Component\HttpFoundation\Request;

  /**
   * @coversDefaultClass \Drupal\Core\DrupalKernel
   * @group DrupalKernel
   */
  class DrupalKernelTest extends UnitTestCase {

    /**
     * Tests hostname validation with settings.
     *
     * @covers ::setupTrustedHosts
     * @dataProvider providerTestTrustedHosts
     */
    public function testTrustedHosts($host, $server_name, $message, $expected = FALSE) {
      $request = new Request();

      $trusted_host_patterns = [
        '^example\.com$',
        '^.+\.example\.com$',
        '^example\.org',
        '^.+\.example\.org',
      ];

      if (!empty($host)) {
        $request->headers->set('HOST', $host);
      }

      $request->server->set('SERVER_NAME', $server_name);

      $method = new \ReflectionMethod('Drupal\Core\DrupalKernel', 'setupTrustedHosts');
      $method->setAccessible(TRUE);
      $valid_host = $method->invoke(NULL, $request, $trusted_host_patterns);

      $this->assertSame($expected, $valid_host, $message);

      // Reset the trusted hosts because it is statically stored on the request.
      $method->invoke(NULL, $request, []);
      // Reset the request factory because it is statically stored on the request.
      Request::setFactory(NULL);
    }

    /**
     * Provides test data for testTrustedHosts().
     */
    public function providerTestTrustedHosts() {
      $data = [];

      // Tests canonical URL.
      $data[] = [
        'www.example.com',
        'www.example.com',
        'canonical URL is trusted',
        TRUE,
      ];

      // Tests missing hostname for HTTP/1.0 compatibility where the Host
      // header is optional.
      $data[] = [NULL, 'www.example.com', 'empty Host is valid', TRUE];

      // Tests the additional patterns from the settings.
      $data[] = [
        'example.com',
        'www.example.com',
        'host from settings is trusted',
        TRUE,
      ];
      $data[] = [
        'subdomain.example.com',
        'www.example.com',
        'host from settings is trusted',
        TRUE,
      ];
      $data[] = [
        'www.example.org',
        'www.example.com',
        'host from settings is trusted',
        TRUE,
      ];
      $data[] = [
        'example.org',
        'www.example.com',
        'host from settings is trusted',
        TRUE,
      ];

      // Tests mismatch.
      $data[] = [
        'www.blackhat.com',
        'www.example.com',
        'unspecified host is untrusted',
        FALSE,
      ];

      return $data;
    }

    /**
     * Tests site path finding.
     *
     * This test is run in a separate process since it defines DRUPAL_ROOT. This
     * stops any possible pollution of other tests.
     *
     * @covers ::findSitePath
     * @runInSeparateProcess
     */
    public function testFindSitePath() {
      $vfs_root = vfsStream::setup('drupal_root');
      $sites_php = <<<'EOD'
<?php
$sites['8888.www.example.org'] = 'example';
EOD;

      // Create the expected directory structure.
      vfsStream::create([
        'sites' => [
          'sites.php' => $sites_php,
          'example' => [
            'settings.php' => 'test',
          ],
        ],
      ]);

      $request = new Request();
      $request->server->set('SERVER_NAME', 'www.example.org');
      $request->server->set('SERVER_PORT', '8888');
      $request->server->set('SCRIPT_NAME', '/index.php');
      $this->assertEquals('sites/example', DrupalKernel::findSitePath($request, TRUE, $vfs_root->url('drupal_root')));
      $this->assertEquals('sites/example', DrupalKernel::findSitePath($request, FALSE, $vfs_root->url('drupal_root')));
    }

  }

  /**
   * A fake autoloader for testing
   */
  class fakeAutoloader {

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend
     *   Whether to prepend the autoloader or not
     */
    public function register($prepend = FALSE) {
      spl_autoload_register([$this, 'loadClass'], TRUE, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister() {
      spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Loads the given class or interface.
     *
     * @return null
     *   This class never loads.
     */
    public function loadClass() {
      return NULL;
    }

    /**
     * Finds a file by class name while caching lookups to APC.
     *
     * @return null
     *   This class never finds.
     */
    public function findFile() {
      return NULL;
    }

  }
}

namespace {

  if (!function_exists('drupal_valid_test_ua')) {

    function drupal_valid_test_ua($new_prefix = NULL) {
      return FALSE;
    }

  }
}
