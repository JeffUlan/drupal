<?php
/**
 * @file
 * Contains \Drupal\update\UpdateManagerInterface.
 */

namespace Drupal\update;

/**
 * Manages project update information.
 */
interface UpdateManagerInterface {

  /**
   * Fetches an array of installed and enabled projects.
   *
   * This is only responsible for generating an array of projects (taking into
   * account projects that include more than one module or theme). Other
   * information like the specific version and install type (official release,
   * dev snapshot, etc) is handled later in update_process_project_info() since
   * that logic is only required when preparing the status report, not for
   * fetching the available release data.
   *
   * This array is fairly expensive to construct, since it involves a lot of
   * disk I/O, so we store the results. However, since this is not the data
   * about available updates fetched from the network, it is acceptable to
   * invalidate it somewhat quickly. If we keep this data for very long, site
   * administrators are more likely to see incorrect results if they upgrade to
   * a newer version of a module or theme but do not visit certain pages that
   * automatically clear this data.
   *
   * @return array
   *   An associative array of currently enabled projects keyed by the
   *   machine-readable project short name. Each project contains:
   *   - name: The machine-readable project short name.
   *   - info: An array with values from the main .info.yml file for this
   *     project.
   *     - name: The human-readable name of the project.
   *     - package: The package that the project is grouped under.
   *     - version: The version of the project.
   *     - project: The Drupal.org project name.
   *     - datestamp: The date stamp of the project's main .info.yml file.
   *     - _info_file_ctime: The maximum file change time for all of the
   *       .info.yml
   *       files included in this project.
   *   - datestamp: The date stamp when the project was released, if known.
   *   - includes: An associative array containing all projects included with
   *     this project, keyed by the machine-readable short name with the
   *     human-readable name as value.
   *   - project_type: The type of project. Allowed values are 'module' and
   *     'theme'.
   *   - project_status: This indicates if the project is enabled and will
   *     always be TRUE, as the function only returns enabled projects.
   *   - sub_themes: If the project is a theme it contains an associative array
   *     of all sub-themes.
   *   - base_themes: If the project is a theme it contains an associative array
   *     of all base-themes.
   *
   * @see update_process_project_info()
   * @see update_calculate_project_data()
   * @see \Drupal\update\UpdateManager::projectStorage()
   */
  public function getProjects();

  /**
   * Processes a step in batch for fetching available update data.
   *
   * @param array $context
   *   Reference to an array used for Batch API storage.
   */
  public function fetchDataBatch(&$context);

  /**
   * Clears out all the available update data and initiates re-fetching.
   */
  public function refreshUpdateData();

  /**
   * Retrieves update storage data or empties it.
   *
   * Two very expensive arrays computed by this module are the list of all
   * installed modules and themes (and .info.yml data, project associations,
   * etc), and the current status of the site relative to the currently
   * available releases. These two arrays are stored and used whenever possible.
   * The data is cleared whenever the administrator visits the status report,
   * available updates report, or the module or theme administration pages,
   * since we should always recompute the most current values on any of those
   * pages.
   *
   * Note: while both of these arrays are expensive to compute (in terms of disk
   * I/O and some fairly heavy CPU processing), neither of these is the actual
   * data about available updates that we have to fetch over the network from
   * updates.drupal.org. That information is stored in the
   * 'update_available_releases' collection -- it needs to persist longer than 1
   * hour and never get invalidated just by visiting a page on the site.
   *
   * @param string $key
   *   The key of data to return. Valid options are 'update_project_data' and
   *   'update_project_projects'.
   *
   * @return array
   *   The stored value of the $projects array generated by
   *   update_calculate_project_data() or
   *   \Drupal\Update\UpdateManager::getProjects(), or an empty array when the
   *   storage is cleared.
   *   array when the storage is cleared.
   */
  public function projectStorage($key);
}
