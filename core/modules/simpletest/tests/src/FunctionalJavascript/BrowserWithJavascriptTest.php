<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\FunctionalJavascript\BrowserWithJavascriptTest.
 */

namespace Drupal\Tests\simpletest\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests if we can execute JavaScript in the browser.
 *
 * @group javascript
 */
class BrowserWithJavascriptTest extends JavascriptTestBase {

  public function testJavascript() {
    $this->drupalGet('<front>');
    $session = $this->getSession();

    $session->resizeWindow(400, 300);
    $javascript = <<<JS
    (function(){
        var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight|| g.clientHeight;
        return x == 400 && y == 300;
    }());
JS;
    $this->assertJsCondition($javascript);
  }

  public function testAssertJsCondition() {
    $this->drupalGet('<front>');
    $session = $this->getSession();

    $session->resizeWindow(500, 300);
    $javascript = <<<JS
    (function(){
        var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight || e.clientHeight|| g.clientHeight;
        return x == 400 && y == 300;
    }());
JS;

    // We expected the following assertion to fail because the window has been
    // re-sized to have a width of 500 not 400.
    $this->setExpectedException(\PHPUnit_Framework_AssertionFailedError::class);
    $this->assertJsCondition($javascript, 100);
  }

}
