<?php
/**
 * QuickApps class
 *
 * PHP version 5
 *
 * @package  QuickApps.Controller.Plugin.System.Lib
 * @version  1.0
 * @author   Christopher Castro <chris@quickapps.es>
 * @link     http://www.quickappscms.org
 */
class QuickApps {
/**
 * Holds temporary information generated and used by some methods.
 *
 * @var array
 */
    private static $__tmp = array();

/**
 * The built in detectors used with `is()`. Can be modified with `addDetector()`.
 *
 * @var array
 */
    protected static $_detectors = array(
        'view' => array(
            'frontpage' => array('self', '__viewIsFrontpage'),
            'login' => array('self', '__viewIsLogin'),
            'admin' => array('self', '__viewIsAdmin'),
            'frontend' => array('self', '__viewIsFrontend'),
            'backend' => array('self', '__viewIsBackend'),
            'search' => array('self', '__viewIsSearch'),
            'rss' => array('self', '__viewIsRss'),
            'node' => array('self', '__viewIsNode')
        ),
        'user' => array(
            'admin' => array('self', '__userIsAdmin'),
            'logged' => array('self', '__userIsLogged'),
            'authorized' => array('self', '__userIsAuthorized')
        ),
        'theme' => array(
            'core' => array('self', '__themeIsCore')
        ),
        'module' => array(
            'core' => array('self', '__moduleIsCore'),
            'field' => array('self', '__moduleIsField'),
            'theme' => array('self', '__moduleIsTheme')
        )
    );

/**
 * Detector method. Uses the built in detection rules
 * as well as additional rules defined with QuickApps::addDetector()
 * Any detector can be called as `is($detect)`.
 *
 * # Built-in detectors:
 * - is('view.frontpage'): is frontpage ?
 * - is('view.login'): is login screen ?
 * - is('view.admin'): is admin prefix ?
 * - is('view.frontend'): is front site ?
 * - is('view.backend'): same as `view.admin`
 * - is('view.search'): is search results page ?
 * - is('view.rss'): is rss feed page ?
 * - is('view.node'): is node details page ?
 * - is('user.logged'): is user logged in?
 * - is('user.admin'): has user admin privileges ?
 * - is('user.authorized', 'AcoPath'): is user allowed to use AcoPath ?
 * - is('theme.core', 'ThemeName'): is `ThemeName` a core theme ?
 * - is('module.core', 'ModuleName'): is `ModuleName` a core module ?
 * - is('module.field', 'ModuleName'): is `ModuleName` a field app ?
 * - is('module.theme', 'ModuleName'): is `ModuleName` a theme app ?
 *
 * ## Example
 * Is actual request site's frontpage ?
 * {{{
 *  $this->Layout->is('view.frontpage');
 * }}}
 *
 * @param string $detect Dot-Syntax unsersored_detector_name and group name. e.g.: `group.detector_name`
 * @param mixed $p Optional parameter for callback methods
 * @return boolean
 */
    public static function is($detect, $p = null) {
        $detect = strtolower($detect);
        list($group, $check) = pluginSplit($detect);

        if (isset(self::$_detectors[$group][$check]) &&
            is_callable(self::$_detectors[$group][$check])
        ) {
            return call_user_func(self::$_detectors[$group][$check], $p);
        } else {
            return false;
        }
    }

/**
 * Add a new detector to the list of detectors.
 * All detector callbacks are grouped by category, this allows to group all
 * detectors by module name and avoid collisions between each other.
 *
 * ### Example
 * {{{
 *  QuickApps::addDetector('my_module.detector_name', array('MyModuleHookHelper', 'detector_handler'));
 * }}}
 *
 * The above will register `detector_name` on `my_module` category.
 * Also, we are using MyModule's Hook Helper class to register the callback method.
 * This last should looks:
 *
 * # MyModuleHookHelper.php
 * {{{
 *  class MyModuleHookHelper extends AppHelper {
 *      public static function detector_handler() {
 *          return (detector login here);
 *      }
 *  }
 * }}}
 *
 * @param string $detect Dot-Syntax detector name
 * @param array $callback Array with ClassName and Method Name
 * @return void
 */
    public static function addDetector($detect, $callback) {
        $detect = strtolower($detect);
        list($group, $check) = pluginSplit($detect);

        if ($group && $check && is_array($callback)) {
            self::$_detectors[$group][$check] = $callback;
        }
    }

/**
 * Translation function, domain search order:
 * 1- Current plugin
 * 2- Default
 * 3- Translatable entries cache
 *
 * @param string $singular String to translate.
 * @return string The translated string.
 */
    public static function __t($singular = false, $args = null) {
        if (!$singular) {
            return;
        }

        App::uses('I18n', 'I18n');

        $route = class_exists('Router') ? Router::getParams() : null;

        if (isset($route['plugin']) && !empty($route['plugin'])) {
            // 1º look in plugin
            $translated = I18n::translate($singular, null, Inflector::underscore($route['plugin']));
        } else {
            $translated = $singular;
        }

        if ($translated === $singular) {
            // 2º look in default
            $translated = I18n::translate($singular, null, 'default');
        }

        if ($translated === $singular) {
            // 3º look in transtalion db-cache
            $cache = Cache::read(md5($singular) . '_' . Configure::read('Config.language'), 'i18n');
            $translated = $cache ? $cache: $singular;
        }

        if ($args === null) {
            return $translated;
        }

        return vsprintf($translated, $args);
    }

/**
 * Returns the roles to which user belongs.
 *
 * @return array List of user's roles.
 */
    public static function userRoles() {
        $roles = array();

        if (!self::__userIsLogged()) {
            $roles[] = 3;
        } else {
            $roles = CakeSession::read('Auth.User.role_id');
        }

        return $roles;
    }

/**
 * Return only the methods for the indicated object.
 * It will strip out the inherited methods.
 *
 * @return array List of methods.
 */
    public static function get_this_class_methods($class) {
        $methods = array();
        $primary = get_class_methods($class);

        if ($parent = get_parent_class($class)) {
            $secondary = get_class_methods($parent);
            $methods = array_diff($primary, $secondary);
        } else {
            $methods = $primary;
        }

        return $methods;
    }

/**
 * Create Unique Arrays using an md5 hash
 *
 * @param array $array
 * @return array
 */
    public static function array_unique_assoc($array, $preserveKeys = false) {
        $arrayRewrite = array();
        $arrayHashes = array();

        foreach ($array as $key => $item) {
            $hash = md5(serialize($item));

            if (!isset($arrayHashes[$hash])) {
                $arrayHashes[$hash] = $hash;

                if ($preserveKeys) {
                    $arrayRewrite[$key] = $item;
                } else {
                    $arrayRewrite[] = $item;
                }
            }
        }

        return $arrayRewrite;
    }

/**
 * Strip language prefix from the given URL.
 * e.g.: `http://site.com/eng/some-url` becomes http://site.com/some-url`
 *
 * @param string $url URL to replace.
 * @return string URL with no language prefix.
 */
    public static function strip_language_prefix($url) {
        $url = preg_replace('/\/[a-z]{3}\//', '/', $url);

        return $url;
    }

/**
 * Replace the first ocurrence only.
 *
 * @param string $str_pattern What to find for.
 * @param string $str_replacement The replacement for $str_pattern.
 * @param string $string The original to find and replace.
 * @return string
 */
    public static function str_replace_once($str_pattern, $str_replacement, $string) {
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);

            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
    }

/**
 * Check if the given module name belongs to QA's Core.
 *
 * @param string $module Module name to check.
 * @return bool TRUE if module is a core module, FALSE otherwise.
 */
    private static function __moduleIsCore($module) {
        $module = Inflector::camelize($module);

        if (CakePlugin::loaded($module)) {
            $path = CakePlugin::path($module);

            if (strpos($path, APP . 'Plugin' . DS) !== false) {
                return true;
            }
        }

        return false;
    }

/**
 * Check if the given `plugin` name is a QA Field.
 *
 * @param string $module Module name to check.
 * @return bool TRUE if module is a field, FALSE otherwise.
 */
    private static function __moduleIsField($module) {
        $module = Inflector::camelize($module);

        if (CakePlugin::loaded($module)) {
            $path = CakePlugin::path($module);

            if (strpos($path, DS . 'Fields' . DS) !== false) {
                return true;
            }
        }

        return false;
    }

/**
 * Check if the given module name belongs to some theme.
 *
 * @param string $module Module name to check.
 * @return bool TRUE if module is a field, FALSE otherwise.
 */
    private static function __moduleIsTheme($module) {
        $module = Inflector::camelize($module);

        if (CakePlugin::loaded($module)) {
            $path = CakePlugin::path($module);

            if (strpos($path, DS . 'Themed' . DS) !== false) {
                return true;
            }
        }

        return false;
    }

/**
 * Check if the given theme name belongs to QA Core installation.
 *
 * @param string $theme Theme name to check.
 * @return bool TRUE if theme is a core theme, FALSE otherwise.
 */
    private static function __themeIsCore($theme) {
        $theme = Inflector::camelize($theme);
        $theme = strpos($theme, 'Theme') !== 0 ? "Theme{$theme}" : $theme;

        if (CakePlugin::loaded($theme)) {
            $app_path = CakePlugin::path($theme);

            if (strpos($app_path, APP . 'View' . DS . 'Themed' . DS) !== false) {
                return true;
            }
        }

        return false;
    }

/**
 * Checks if current view site's front page.
 *
 * @return boolean
 */
    private static function __viewIsFrontpage() {
        $params = Router::getParams();

        return (
            $params['plugin'] == 'Node' &&
            $params['action'] == 'index' &&
            !Configure::read('Variable.site_frontpage')
        );
    }

/**
 * Checks if current view is the login screen.
 *
 * @return boolean
 */
    private static function __viewIsLogin() {
        $params = Router::getParams();

        return (
            $params['plugin'] == 'user' &&
            $params['controller'] == 'user' &&
            in_array($params['action'], array('login', 'admin_login'))
        );
    }

/**
 * Checks if current view is a `backend` view.
 *
 * @return boolean
 */
    private static function __viewIsAdmin() {
        $params = Router::getParams();

        return isset($params['admin']) && $params['admin'];
    }

/**
 * Checks if current view is not a `backend` view.
 *
 * @return boolean
 */
    private static function __viewIsFrontend() {
        return !self::__viewIsAdmin();
    }

/**
 * Checks if current view is a `backend` view.
 * Alias for QuickApps::is('view.admin').
 *
 * @return boolean
 */
    private static function __viewIsBackend() {
        return self::__viewIsAdmin();
    }

/**
 * Checks if current view is a `backend` view.
 * Alias for QuickApps::is('view.admin').
 *
 * @return boolean
 */
    private static function __viewIsSearch() {
        $params = Router::getParams();

        return (
            $params['plugin'] == 'node' &&
            $params['controller'] == 'node' &&
            $params['action'] == 'search'
        );
    }

/**
 * Checks if current view is a `backend` view.
 * Alias for QuickApps::is('view.admin').
 *
 * @return boolean
 */
    private static function __viewIsRss() {
        $params = Router::getParams();

        return (
            $params['plugin'] == 'node' &&
            $params['controller'] == 'node' &&
            $params['action'] == 'search' &&
            isset($params['pass'][1])
        );
    }

/**
 * Checks if current view is a `backend` view.
 * Alias for QuickApps::is('view.admin').
 *
 * @return boolean
 */
    private static function __viewIsNode() {
        $params = Router::getParams();

        return (
            strtolower($params['plugin']) == 'node' &&
            $params['controller'] == 'node' &&
            $params['action'] == 'details'
        );
    }

/**
 * Checks if user has admin privileges.
 *
 * @return boolean
 */
    private static function __userIsAdmin() {
        return in_array(1, (array)self::userRoles());
    }

/**
 * Checks if user is logged in.
 *
 * @return boolean
 */
    private static function __userIsLogged() {
        return CakeSession::check('Auth.User.id');
    }

/**
 * Checks if user is allowed to access the specified ACO.
 * ACO path syntax: `Module.Controller.action`
 *
 * @param string $acoPath DotSyntax path to aco. e.g.: `Block.Manage.admin_index`
 * @return boolean
 */
    private static function __userIsAuthorized($acoPath) {
        if (isset(self::$__tmp['authorized'][$acoPath])) {
            return self::$__tmp['authorized'][$acoPath];
        }

        $roles = self::userRoles();

        if (in_array(1, $roles)) {
            self::$__tmp['authorized'][$acoPath] = true;

            return true;
        }

        list($plugin, $controller, $action) = explode('.', $acoPath);

        if ($plugin && $controller && $action) {
            $Aco = ClassRegistry::init('Aco');
            $Permission = ClassRegistry::init('Permission');
            $conditions = array();
            $p = $Aco->find('first', array('conditions' => array('Aco.parent_id' => null, 'Aco.alias' => $plugin), 'recursive' => -1));
            $c = $Aco->find('first', array('conditions' => array('Aco.parent_id' => $p['Aco']['id']), 'recursive' => -1));
            $a = $Aco->find('first', array('conditions' => array('Aco.parent_id' => $c['Aco']['id']), 'recursive' => -1));

            foreach($roles as $role) {
                $conditions['OR'][] = array(
                    'AND' => array(
                        'Permission.aro_id' => $role,
                        'Permission.aco_id ' => $a['Aco']['id'],
                        'Permission._create' => 1,
                        'Permission._read' => 1,
                        'Permission._update' => 1,
                        'Permission._delete' => 1
                    )
                );
            }

            $authorized = $Permission->find('count', array('conditions' => $conditions)) > 0;
            self::$__tmp['authorized'][$acoPath] = $authorized;

            return $authorized;
        }

        return false;
    }
}