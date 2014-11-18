<?php

/**
 * @file
 * Contains \Drupal\Core\Session\AccountSwitcher.
 */

namespace Drupal\Core\Session;

/**
 * An implementation of AccountSwitcherInterface.
 *
 * This allows for safe switching of user accounts by ensuring that session
 * data for one user is not leaked in to others. It also provides a stack that
 * allows reverting to a previous user after switching.
 */
class AccountSwitcher implements AccountSwitcherInterface {

  /**
   * A stack of previous overridden accounts.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $accountStack = array();

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser = array();

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The original state of session saving prior to account switching.
   *
   * @var bool
   */
  protected $originalSessionSaving;

  /**
   * Constructs a new AccountSwitcher.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   */
  public function __construct(AccountProxyInterface $current_user, SessionManagerInterface $session_manager) {
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function switchTo(AccountInterface $account) {
    // Prevent session information from being saved and push previous account.
    if (!isset($this->originalSessionSaving)) {
      // Ensure that only the first session saving status is saved.
      $this->originalSessionSaving = $this->sessionManager->isEnabled();
    }
    $this->sessionManager->disable();
    array_push($this->accountStack, $this->currentUser->getAccount());
    $this->currentUser->setAccount($account);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function switchBack() {
    // Restore the previous account from the stack.
    if (!empty($this->accountStack)) {
      $this->currentUser->setAccount(array_pop($this->accountStack));
    }
    else {
      throw new \RuntimeException('No more accounts to revert to.');
    }
    // Restore original session saving status if all account switches are
    // reverted.
    if (empty($this->accountStack)) {
      if ($this->originalSessionSaving) {
        $this->sessionManager->enable();
      }
    }
    return $this;
  }

}
