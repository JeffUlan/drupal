<?php

/**
 * @file
 * Contains \Drupal\Core\Render\RendererInterface.
 */

namespace Drupal\Core\Render;

/**
 * Defines an interface for turning a render array into a string.
 */
interface RendererInterface {

  /**
   * Renders final HTML given a structured array tree.
   *
   * Calls ::render() in such a way that #post_render_cache callbacks are
   * applied.
   *
   * Should therefore only be used in occasions where the final rendering is
   * happening, just before sending a Response:
   * - system internals that are responsible for rendering the final HTML
   * - render arrays for non-HTML responses, such as feeds
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return string
   *   The rendered HTML.
   *
   * @see ::render()
   */
  public function renderRoot(&$elements);

  /**
   * Renders final HTML in situations where no assets are needed.
   *
   * Calls ::render() in such a way that #post_render_cache callbacks are
   * applied.
   *
   * Useful for e.g. rendering the values of tokens or e-mails, which need a
   * render array being turned into a string, but don't need any of the
   * bubbleable metadata (the attached assets the cache tags).
   *
   * Some of these are a relatively common use case and happen *within* a
   * ::renderRoot() call, but that is generally highly problematic (and hence an
   * exception is thrown when a ::renderRoot() call happens within another
   * ::renderRoot() call). However, in this case, we only care about the output,
   * not about the bubbling. Hence this uses a separate render stack, to not
   * affect the parent ::renderRoot() call.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return string
   *   The rendered HTML.
   *
   * @see ::renderRoot()
   * @see ::render()
   */
  public function renderPlain(&$elements);

  /**
   * Renders HTML given a structured array tree.
   *
   * Renderable arrays have two kinds of key/value pairs: properties and
   * children. Properties have keys starting with '#' and their values influence
   * how the array will be rendered. Children are all elements whose keys do not
   * start with a '#'. Their values should be renderable arrays themselves,
   * which will be rendered during the rendering of the parent array. The markup
   * provided by the children is typically inserted into the markup generated by
   * the parent array.
   *
   * An important aspect of rendering is the bubbling of rendering metadata:
   * cache tags, attached assets and #post_render_cache metadata all need to be
   * bubbled up. That information is needed once the rendering to a HTML string
   * is completed: the resulting HTML for the page must know by which cache tags
   * it should be invalidated, which (CSS and JavaScript) assets must be loaded,
   * and which #post_render_cache callbacks should be executed. A stack data
   * structure is used to perform this bubbling.
   *
   * The process of rendering an element is recursive unless the element defines
   * an implemented theme hook in #theme. During each call to
   * Renderer::render(), the outermost renderable array (also known as an
   * "element") is processed using the following steps:
   *   - If this element has already been printed (#printed = TRUE) or the user
   *     does not have access to it (#access = FALSE), then an empty string is
   *     returned.
   *   - If no stack data structure has been created yet, it is done now. Next,
   *     an empty \Drupal\Core\Render\BubbleableMetadata is pushed onto the
   *     stack.
   *   - If this element has #cache defined then the cached markup for this
   *     element will be returned if it exists in Renderer::render()'s cache. To
   *     use Renderer::render() caching, set the element's #cache property to an
   *     associative array with one or several of the following keys:
   *     - 'keys': An array of one or more keys that identify the element. If
   *       'keys' is set, the cache ID is created automatically from these keys.
   *     - 'contexts': An array of one or more cache context IDs. These are
   *       converted to a final value depending on the request. (e.g. 'user' is
   *       mapped to the current user's ID.)
   *     - 'cid': Specify the cache ID directly. Either 'keys' or 'cid' is
   *       required. If 'cid' is set, 'keys' is ignored. Use only if you have
   *       special requirements.
   *     - 'expire': Set to one of the cache lifetime constants.
   *     - 'bin': Specify a cache bin to cache the element in. Default is
   *       'default'.
   *     When there is a render cache hit, there is no rendering work left to be
   *     done, so the stack must be updated. The empty (and topmost) frame that
   *     was just pushed onto the stack is updated with all bubbleable rendering
   *     metadata from the element retrieved from render cache. Then, this stack
   *     frame is bubbled: the two topmost frames are popped from the stack,
   *     they are merged, and the result is pushed back onto the stack.
   *   - If this element has #type defined and the default attributes for this
   *     element have not already been merged in (#defaults_loaded = TRUE) then
   *     the defaults for this type of element, defined in hook_element_info(),
   *     are merged into the array. #defaults_loaded is set by functions that
   *     process render arrays and call element_info() before passing the array
   *     to Renderer::render(), such as form_builder() in the Form API.
   *   - If this element has an array of #pre_render functions defined, they are
   *     called sequentially to modify the element before rendering. After all
   *     the #pre_render functions have been called, #printed is checked a
   *     second time in case a #pre_render function flags the element as
   *     printed. If #printed is set, we return early and hence no rendering
   *     work is left to be done, similarly to a render cache hit. Once again,
   *     the empty (and topmost) frame that was just pushed onto the stack is
   *     updated with all bubbleable rendering metadata from the element whose
   *     #printed = TRUE.
   *     Then, this stack frame is bubbled: the two topmost frames are popped
   *     from the stack, they are merged, and the result is pushed back onto the
   *     stack.
   *   - The child elements of this element are sorted by weight using uasort()
   *     in \Drupal\Core\Render\Element::children(). Since this is expensive,
   *     when passing already sorted elements to Renderer::render(), for example
   *     from a database query, set $elements['#sorted'] = TRUE to avoid sorting
   *     them a second time.
   *   - The main render phase to produce #children for this element takes
   *     place:
   *     - If this element has #theme defined and #theme is an implemented theme
   *       hook/suggestion then ThemeManagerInterface::render() is called and
   *       must render both the element and its children. If #render_children is
   *       set, ThemeManagerInterface::render() will not be called.
   *       #render_children is usually only set internally by
   *       ThemeManagerInterface::render() so that we can avoid the situation
   *       where Renderer::render() called from within a theme preprocess
   *       function creates an infinite loop.
   *     - If this element does not have a defined #theme, or the defined #theme
   *       hook is not implemented, or #render_children is set, then
   *       Renderer::render() is called recursively on each of the child
   *       elements of this element, and the result of each is concatenated onto
   *       #children. This is skipped if #children is not empty at this point.
   *     - Once #children has been rendered for this element, if #theme is not
   *       implemented and #markup is set for this element, #markup will be
   *       prepended to #children.
   *   - If this element has #states defined then JavaScript state information
   *     is added to this element's #attached attribute by
   *     drupal_process_states().
   *   - If this element has #attached defined then any required libraries,
   *     JavaScript, CSS, or other custom data are added to the current page by
   *     drupal_process_attached().
   *   - If this element has an array of #theme_wrappers defined and
   *     #render_children is not set, #children is then re-rendered by passing
   *     the element in its current state to ThemeManagerInterface::render()
   *     successively for each item in #theme_wrappers. Since #theme and
   *     #theme_wrappers hooks often define variables with the same names it is
   *     possible to explicitly override each attribute passed to each
   *     #theme_wrappers hook by setting the hook name as the key and an array
   *     of overrides as the value in #theme_wrappers array.
   *     For example, if we have a render element as follows:
   *     @code
   *     array(
   *       '#theme' => 'image',
   *       '#attributes' => array('class' => array('foo')),
   *       '#theme_wrappers' => array('container'),
   *     );
   *     @endcode
   *     and we need to pass the class 'bar' as an attribute for 'container', we
   *     can rewrite our element thus:
   *     @code
   *     array(
   *       '#theme' => 'image',
   *       '#attributes' => array('class' => array('foo')),
   *       '#theme_wrappers' => array(
   *         'container' => array(
   *           '#attributes' => array('class' => array('bar')),
   *         ),
   *       ),
   *     );
   *      @endcode
   *   - If this element has an array of #post_render functions defined, they
   *     are called sequentially to modify the rendered #children. Unlike
   *     #pre_render functions, #post_render functions are passed both the
   *     rendered #children attribute as a string and the element itself.
   *   - If this element has #prefix and/or #suffix defined, they are
   *     concatenated to #children.
   *   - The rendering of this element is now complete. The next step will be
   *     render caching. So this is the perfect time to update the stack. At
   *     this point, children of this element (if any), have been rendered also,
   *     and if there were any, their bubbleable rendering metadata will have
   *     been bubbled up into the stack frame for the element that is currently
   *     being rendered. The render cache item for this element must contain the
   *     bubbleable rendering metadata for this element and all of its children.
   *     However, right now, the topmost stack frame (the one for this element)
   *     currently only contains the metadata for the children. Therefore, the
   *     topmost stack frame is updated with this element's metadata, and then
   *     the element's metadata is replaced with the metadata in the topmost
   *     stack frame. This element now contains all bubbleable rendering
   *     metadata for this element and all its children, so it's now ready for
   *     render caching.
   *   - If this element has #cache defined, the rendered output of this element
   *     is saved to Renderer::render()'s internal cache. This includes the
   *     changes made by #post_render.
   *   - If this element has an array of #post_render_cache functions defined,
   *     or any of its children has (which we would know thanks to the stack
   *     having been updated just before the render caching step), they are
   *     called sequentially to replace placeholders in the final #markup and
   *     extend #attached. Placeholders must contain a unique token, to
   *     guarantee that e.g. samples of placeholders are not replaced also. But,
   *     since #post_render_cache callbacks add attach additional assets, the
   *     correct bubbling of those must once again be taken into account. This
   *     final stage of rendering should be considered as if it were the parent
   *     of the current element, because it takes that as its input, and then
   *     alters its #markup. Hence, just before calling the #post_render_cache
   *     callbacks, a new empty frame is pushed onto the stack, where all assets
   *     #attached during the execution of those callbacks will end up in. Then,
   *     after the execution of those callbacks, we merge that back into the
   *     element. Note that these callbacks run always: when hitting the render
   *     cache, when missing, or when render caching is not used at all. This is
   *     done to allow any Drupal module to customize other render arrays
   *     without breaking the render cache if it is enabled, and to not require
   *     it to use other logic when render caching is disabled.
   *   - Just before finishing the rendering of this element, this element's
   *     stack frame (the topmost one) is bubbled: the two topmost frames are
   *     popped from the stack, they are merged and the result is pushed back
   *     onto the stack.
   *     So if this element e.g. was a child element, then a new frame was
   *     pushed onto the stack element at the beginning of rendering this
   *     element, it was updated when the rendering was completed, and now we
   *     merge it with the frame for the parent, so that the parent now has the
   *     bubbleable rendering metadata for its child.
   *   - #printed is set to TRUE for this element to ensure that it is only
   *     rendered once.
   *   - The final value of #children for this element is returned as the
   *     rendered output.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   * @param bool $is_root_call
   *   (Internal use only.) Whether this is a recursive call or not. See
   *   ::renderRoot().
   *
   * @return string
   *   The rendered HTML.
   *
   * @throws \LogicException
   *   If a root call to ::render() does not result in an empty stack, this
   *   indicates an erroneous ::render() root call (a root call within a
   *   root call, which makes no sense). Therefore, a logic exception is thrown.
   * @throws \Exception
   *   If a #pre_render callback throws an exception, it is caught to reset the
   *   stack used for bubbling rendering metadata, and then the exception is re-
   *   thrown.
   *
   * @see \Drupal\Core\Render\ElementInfoManagerInterface::getInfo()
   * @see \Drupal\Core\Theme\ThemeManagerInterface::render()
   * @see drupal_process_states()
   * @see drupal_process_attached()
   * @see ::renderRoot()
   */
  public function render(&$elements, $is_root_call = FALSE);

  /**
   * Gets a cacheable render array for a render array and its rendered output.
   *
   * Given a render array and its rendered output (HTML string), return an array
   * data structure that allows the render array and its associated metadata to
   * be cached reliably (and is serialization-safe).
   *
   * If Drupal needs additional rendering metadata to be cached at some point,
   * consumers of this method will continue to work. Those who only cache
   * certain parts of a render array will cease to work.
   *
   * @param array $elements
   *   A renderable array, on which ::render() has already been invoked.
   *
   * @return array
   *   An array representing the cacheable data for this render array.
   */
  public function getCacheableRenderArray(array $elements);

  /**
   * Merges the bubbleable rendering metadata o/t 2nd render array with the 1st.
   *
   * @param array $a
   *   A render array.
   * @param array $b
   *   A render array.
   *
   * @return array
   *   The first render array, modified to also contain the bubbleable rendering
   *   metadata of the second render array.
   *
   * @see \Drupal\Core\Render\BubbleableMetadata
   */
  public static function mergeBubbleableMetadata(array $a, array $b);

  /**
   * Merges two attachments arrays (which live under the '#attached' key).
   *
   * The values under the 'drupalSettings' key are merged in a special way, to
   * match the behavior of:
   *
   * @code
   *   jQuery.extend(true, {}, $settings_items[0], $settings_items[1], ...)
   * @endcode
   *
   * This means integer indices are preserved just like string indices are,
   * rather than re-indexed as is common in PHP array merging.
   *
   * Example:
   * @code
   * function module1_page_attachments(&$page) {
   *   $page['a']['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
   * }
   * function module2_page_attachments(&$page) {
   *   $page['#attached']['drupalSettings']['foo'] = ['d'];
   * }
   * // When the page is rendered after the above code, and the browser runs the
   * // resulting <SCRIPT> tags, the value of drupalSettings.foo is
   * // ['d', 'b', 'c'], not ['a', 'b', 'c', 'd'].
   * @endcode
   *
   * By following jQuery.extend() merge logic rather than common PHP array merge
   * logic, the following are ensured:
   * - Attaching JavaScript settings is idempotent: attaching the same settings
   *   twice does not change the output sent to the browser.
   * - If pieces of the page are rendered in separate PHP requests and the
   *   returned settings are merged by JavaScript, the resulting settings are
   *   the same as if rendered in one PHP request and merged by PHP.
   *
   * @param array $a
   *   An attachments array.
   * @param array $b
   *   Another attachments array.
   *
   * @return array
   *   The merged attachments array.
   */
  public static function mergeAttachments(array $a, array $b);

}
