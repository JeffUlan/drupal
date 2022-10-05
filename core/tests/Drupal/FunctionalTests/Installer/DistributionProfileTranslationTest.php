<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;

/**
 * Tests distribution profile support.
 *
 * @group Installer
 *
 * @see \Drupal\FunctionalTests\Installer\DistributionProfileTest
 */
class DistributionProfileTranslationTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $langcode = 'de';

  /**
   * The distribution profile info.
   *
   * @var array
   */
  protected $info;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // We set core_version_requirement to '*' for the test so that it does not
    // need to be updated between major versions.
    $this->info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Distribution profile',
      'distribution' => [
        'name' => 'My Distribution',
        'langcode' => $this->langcode,
        'install' => [
          'theme' => 'claro',
        ],
      ],
    ];
    // File API functions are not available yet.
    $path = $this->root . DIRECTORY_SEPARATOR . $this->siteDirectory . '/profiles/my_distro';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/my_distro.info.yml", Yaml::encode($this->info));

    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // This step is skipped, because the distribution profile uses a fixed
    // language.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // The language should have been automatically detected, all following
    // screens should be translated already.
    $this->assertSession()->buttonExists('Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $this->assertSession()->elementTextEquals('xpath', '/@dir', 'ltr');

    // Verify that the distribution name appears.
    $this->assertSession()->pageTextContains($this->info['distribution']['name']);
    // Verify that the requested theme is used.
    $this->assertSession()->responseContains($this->info['distribution']['install']['theme']);
    // Verify that the "Choose profile" step does not appear.
    $this->assertSession()->pageTextNotContains('profile');

    parent::setUpSettings();
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getDisplayName());

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->pageTextContains('German');
    $this->assertSession()->pageTextNotContains('English');
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"
ENDPO;
  }

}
