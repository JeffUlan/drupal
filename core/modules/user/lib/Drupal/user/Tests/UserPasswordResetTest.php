<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserPasswordResetTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests resetting a user password.
 */
class UserPasswordResetTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Reset password',
      'description' => 'Ensure that password reset methods work as expected.',
      'group' => 'User',
    );
  }

  /**
   * Tests password reset functionality.
   */
  function testUserPasswordReset() {
    // Create a user.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalLogout();
    // Attempt to reset password.
    $edit = array('name' => $account->name);
    $this->drupalPost('user/password', $edit, t('E-mail new password'));
    // Confirm the password reset.
    $this->assertText(t('Further instructions have been sent to your e-mail address.'), 'Password reset instructions mailed message displayed.');
  }

  /**
   * Attempts login using an expired password reset link.
   */
  function testUserPasswordResetExpired() {
    // Set password reset timeout to 43200 seconds = 12 hours.
    config('user.settings')->set('password_reset_timeout', 43200)->save();
    // Create a user.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    // Load real user object.
    $account = user_load($account->uid, TRUE);
    $this->drupalLogout();

    // To attempt an expired password reset, create a password reset link as if
    // its request time was 60 seconds older than the allowed limit of timeout.
    $timeout = config('user.settings')->get('password_reset_timeout');
    $bogus_timestamp = REQUEST_TIME - $timeout - 60;
    $this->drupalGet("user/reset/$account->uid/$bogus_timestamp/" . user_pass_rehash($account->pass, $bogus_timestamp, $account->login));
    $this->assertText(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'Expired password reset request rejected.');
  }
}
