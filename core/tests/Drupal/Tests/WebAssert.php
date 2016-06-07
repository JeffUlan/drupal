<?php

namespace Drupal\Tests;

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\WebAssert as MinkWebAssert;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Component\Utility\Html;

/**
 * Defines a class with methods for asserting presence of elements during tests.
 */
class WebAssert extends MinkWebAssert {

  /**
   * Checks that specific button exists on the current page.
   *
   * @param string $button
   *   One of id|name|label|value for the button.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function buttonExists($button, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findButton($button);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session, 'button', 'id|name|label|value', $button);
    }

    return $node;
  }

  /**
   * Checks that specific select field exists on the current page.
   *
   * @param string $select
   *   One of id|name|label|value for the select field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function selectExists($select, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->find('named', array(
      'select',
      $this->session->getSelectorsHandler()->xpathLiteral($select),
    ));

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session, 'select', 'id|name|label|value', $select);
    }

    return $node;
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the title is a different one.
   */
  public function titleEquals($expected_title) {
    $title_element = $this->session->getPage()->find('css', 'title');
    if (!$title_element) {
      throw new ExpectationException('No title element found on the page', $this->session);
    }
    $actual_title = $title_element->getText();
    $this->assert($expected_title === $actual_title, 'Title found');
  }

  /**
   * Passes if a link with the specified label is found.
   *
   * An optional link index may be passed.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkExists($label, $index = 0, $message = '') {
    // Cast MarkupInterface objects to string.
    $label = (string) $label;
    $message = ($message ? $message : strtr('Link with label %label found.', ['%label' => $label]));
    $links = $this->session->getPage()->findAll('named', ['link', $label]);
    if (empty($links[$index])) {
      throw new ExpectationException($message);
    }
    $this->assert($links[$index] !== NULL, $message);
  }

  /**
   * Passes if the raw text IS NOT found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  public function assertNoEscaped($raw) {
    $this->pageTextNotContains(Html::escape($raw));
  }

  /**
   * Asserts a condition.
   *
   * The parent method is overridden because it is a private method.
   *
   * @param bool $condition
   *   The condition.
   * @param string $message
   *   The success message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the condition is not fulfilled.
   */
  public function assert($condition, $message) {
    if ($condition) {
      return;
    }

    throw new ExpectationException($message, $this->session->getDriver());
  }

}
