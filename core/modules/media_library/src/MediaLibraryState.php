<?php

namespace Drupal\media_library;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * A value object for the media library state.
 *
 * When the media library is opened it needs several parameters to work
 * properly. These parameters are normally extracted from the current URL, then
 * retrieved from and managed by the MediaLibraryState value object. The
 * following parameters are required in order to open the media library:
 * - media_library_opener_id: The ID of a container service which implements
 *   \Drupal\media_library\MediaLibraryOpenerInterface and is responsible for
 *   interacting with the media library on behalf of the "thing" (e.g., a field
 *   widget or text editor button) which opened it.
 * - media_library_allowed_types: The media types available in the library can
 *   be restricted to a list of allowed types. This should be an array of media
 *   type IDs.
 * - media_library_selected_type: The media library contains tabs to navigate
 *   between the different media types. The selected type contains the ID of the
 *   media type whose tab that should be opened.
 * - media_library_remaining: When the opener wants to limit the amount of media
 *   items that can be selected, it can pass the number of remaining slots. When
 *   the number of remaining slots is a negative number, an unlimited amount of
 *   items can be selected.
 *
 * This object can also carry an optional opener-specific array of arbitrary
 * values, under the media_library_opener_parameters key. These values are
 * included in the hash generated by ::getHash(), so the end user cannot tamper
 * with them either.
 *
 * @see \Drupal\media_library\MediaLibraryOpenerInterface
 *
 * @internal
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
class MediaLibraryState extends ParameterBag {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $parameters = []) {
    $this->validateRequiredParameters($parameters['media_library_opener_id'], $parameters['media_library_allowed_types'], $parameters['media_library_selected_type'], $parameters['media_library_remaining']);
    $parameters += [
      'media_library_opener_parameters' => [],
    ];
    parent::__construct($parameters);
    $this->set('hash', $this->getHash());
  }

  /**
   * Creates a new MediaLibraryState object.
   *
   * @param string $opener_id
   *   The opener ID.
   * @param string[] $allowed_media_type_ids
   *   The allowed media type IDs.
   * @param string $selected_type_id
   *   The selected media type ID.
   * @param int $remaining_slots
   *   The number of remaining items the user is allowed to select or add in the
   *   library.
   * @param array $opener_parameters
   *   (optional) Any additional opener-specific parameter values.
   *
   * @return static
   *   A state object.
   */
  public static function create($opener_id, array $allowed_media_type_ids, $selected_type_id, $remaining_slots, array $opener_parameters = []) {
    $state = new static([
      'media_library_opener_id' => $opener_id,
      'media_library_allowed_types' => $allowed_media_type_ids,
      'media_library_selected_type' => $selected_type_id,
      'media_library_remaining' => $remaining_slots,
      'media_library_opener_parameters' => $opener_parameters,
    ]);
    return $state;
  }

  /**
   * Get the media library state from a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return static
   *   A state object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when the hash query parameter is invalid.
   */
  public static function fromRequest(Request $request) {
    $query = $request->query;

    // Create a MediaLibraryState object through the create method to make sure
    // all validation runs.
    $state = static::create(
      $query->get('media_library_opener_id'),
      $query->get('media_library_allowed_types', []),
      $query->get('media_library_selected_type'),
      $query->get('media_library_remaining'),
      $query->get('media_library_opener_parameters', [])
    );

    // The request parameters need to contain a valid hash to prevent a
    // malicious user modifying the query string to attempt to access
    // inaccessible information.
    if (!$state->isValidHash($query->get('hash'))) {
      throw new BadRequestHttpException("Invalid media library parameters specified.");
    }

    // Once we have validated the required parameters, we restore the parameters
    // from the request since there might be additional values.
    $state->replace($query->all());
    return $state;
  }

  /**
   * Validates the required parameters for a new MediaLibraryState object.
   *
   * @param string $opener_id
   *   The media library opener service ID.
   * @param string[] $allowed_media_type_ids
   *   The allowed media type IDs.
   * @param string $selected_type_id
   *   The selected media type ID.
   * @param int $remaining_slots
   *   The number of remaining items the user is allowed to select or add in the
   *   library.
   *
   * @throws \InvalidArgumentException
   *   If one of the passed arguments is missing or does not pass the
   *   validation.
   */
  protected function validateRequiredParameters($opener_id, array $allowed_media_type_ids, $selected_type_id, $remaining_slots) {
    // The opener ID must be a non-empty string.
    if (!is_string($opener_id) || empty(trim($opener_id))) {
      throw new \InvalidArgumentException('The opener ID parameter is required and must be a string.');
    }

    // The allowed media type IDs must be an array of non-empty strings.
    if (empty($allowed_media_type_ids) || !is_array($allowed_media_type_ids)) {
      throw new \InvalidArgumentException('The allowed types parameter is required and must be an array of strings.');
    }
    foreach ($allowed_media_type_ids as $allowed_media_type_id) {
      if (!is_string($allowed_media_type_id) || empty(trim($allowed_media_type_id))) {
        throw new \InvalidArgumentException('The allowed types parameter is required and must be an array of strings.');
      }
    }

    // The selected type ID must be a non-empty string.
    if (!is_string($selected_type_id) || empty(trim($selected_type_id))) {
      throw new \InvalidArgumentException('The selected type parameter is required and must be a string.');
    }
    // The selected type ID must be present in the list of allowed types.
    if (!in_array($selected_type_id, $allowed_media_type_ids, TRUE)) {
      throw new \InvalidArgumentException('The selected type parameter must be present in the list of allowed types.');
    }

    // The remaining slots must be numeric.
    if (!is_numeric($remaining_slots)) {
      throw new \InvalidArgumentException('The remaining slots parameter is required and must be numeric.');
    }
  }

  /**
   * Get the hash for the state object.
   *
   * @return string
   *   The hashed parameters.
   */
  public function getHash() {
    // Create a hash from the required state parameters and the serialized
    // optional opener-specific parameters. Sort the allowed types and
    // opener parameters so that differences in order do not result in
    // different hashes.
    $allowed_media_type_ids = array_values($this->getAllowedTypeIds());
    sort($allowed_media_type_ids);
    $opener_parameters = $this->getOpenerParameters();
    ksort($opener_parameters);
    $hash = implode(':', [
      $this->getOpenerId(),
      implode(':', $allowed_media_type_ids),
      $this->getSelectedTypeId(),
      $this->getAvailableSlots(),
      serialize($opener_parameters),
    ]);

    return Crypt::hmacBase64($hash, \Drupal::service('private_key')->get() . Settings::getHashSalt());
  }

  /**
   * Validate a hash for the state object.
   *
   * @param string $hash
   *   The hash to validate.
   *
   * @return string
   *   The hashed parameters.
   */
  public function isValidHash($hash) {
    return hash_equals($this->getHash(), $hash);
  }

  /**
   * Returns the ID of the media library opener service.
   *
   * @return string
   *   The media library opener service ID.
   */
  public function getOpenerId() {
    return $this->get('media_library_opener_id');
  }

  /**
   * Returns the media type IDs which can be selected.
   *
   * @return string[]
   *   The media type IDs.
   */
  public function getAllowedTypeIds() {
    return $this->get('media_library_allowed_types');
  }

  /**
   * Returns the selected media type.
   *
   * @return string
   *   The selected media type.
   */
  public function getSelectedTypeId() {
    return $this->get('media_library_selected_type');
  }

  /**
   * Determines if additional media items can be selected.
   *
   * @return bool
   *   TRUE if additional items can be selected, otherwise FALSE.
   */
  public function hasSlotsAvailable() {
    return $this->getAvailableSlots() !== 0;
  }

  /**
   * Returns the number of additional media items that can be selected.
   *
   * When the value is not available in the URL the default is 0. When a
   * negative integer is passed, an unlimited amount of media items can be
   * selected.
   *
   * @return int
   *   The number of additional media items that can be selected.
   */
  public function getAvailableSlots() {
    return $this->getInt('media_library_remaining');
  }

  /**
   * Returns all opener-specific parameter values.
   *
   * @return array
   *   An associative array of all opener-specific parameter values.
   */
  public function getOpenerParameters() {
    return $this->get('media_library_opener_parameters', []);
  }

}
