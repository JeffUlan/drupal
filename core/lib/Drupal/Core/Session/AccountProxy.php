<?php

/**
 * @file
 * Contains \Drupal\Core\Session\AccountProxy.
 */

namespace Drupal\Core\Session;

use Drupal\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A proxied implementation of AccountInterface.
 *
 * The reason why we need an account proxy is that we don't want to have global
 * state directly stored in the container.
 *
 * This proxy object avoids multiple invocations of the authentication manager
 * which can happen if the current user is accessed in constructors. It also
 * allows legacy code to change the current user where the user cannot be
 * directly injected into dependent code.
 */
class AccountProxy implements AccountProxyInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The authentication manager.
   *
   * @var \Drupal\Core\Authentication\AuthenticationManagerInterface
   */
  protected $authenticationManager;

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new AccountProxy.
   *
   * @param \Drupal\Core\Authentication\AuthenticationManagerInterface $authentication_manager
   *   The authentication manager.
   */
  public function __construct(AuthenticationManagerInterface $authentication_manager) {
    $this->authenticationManager = $authentication_manager;
  }

  /**
   * Sets the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    // Reset the current user to ensure that new calls will return the correct
    // user based on the request.
    $this->account = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount(AccountInterface $account) {
    // If the passed account is already proxyed, use the actual account instead
    // to prevent loops.
    if ($account instanceof static) {
      $account = $account->getAccount();
    }
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    if (!isset($this->account)) {
      $this->setAccount($this->authenticationManager->authenticate($this->request));
    }
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getAccount()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    return $this->getAccount()->getRoles($exclude_locked_roles);
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->getAccount()->getHostname();
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->getAccount()->hasPermission($permission);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->getAccount()->getSessionId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSecureSessionId() {
    return $this->getAccount()->getSecureSessionId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionData() {
    return $this->getAccount()->getSessionData();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->getAccount()->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->getAccount()->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($default = NULL) {
    return $this->getAccount()->getPreferredLangcode($default);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($default = NULL) {
    return $this->getAccount()->getPreferredAdminLangcode($default);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->getAccount()->getUsername();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->getAccount()->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->getAccount()->getTimeZone();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->getAccount()->getLastAccessedTime();
  }

}

