<?php
/*
 * Plugin Name: TinyMCE Color Grid
 * Plugin URI: https://wordpress.org/plugins/kt-tinymce-color-grid
 * Description: Extends the TinyMCE color picker with a lot more colors to choose from.
 * Version: 1.4
 * Author: Anagarika Daniel
 * Author URI: http://profiles.wordpress.org/kungtiger
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /language
 * Text Domain: kt-tinymce-color-grid
 */

if (!function_exists('add_action')) {
    ?>
    <!DOCTYPE HTML>
    <html>
        <head>
            <meta charset="UTF-8" />
            <title>WordPress &rsaquo; Oops&hellip;</title>
            <style type="text/css">
                html {
                    background: #f1f1f1; }
                body {
                    margin: 50px auto 2em;
                    padding: 1em 2em;
                    max-width: 700px;
                    background: #fff;
                    color: #444;
                    font-family: "Open Sans", sans-serif;
                    -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.13); }
                h1 {
                    clear: both;
                    margin: 30px 0 0 0;
                    padding: 0;
                    padding-bottom: 7px;
                    border-bottom: 1px solid #dadada;
                    color: #666;
                    font: 24px "Open Sans", sans-serif; }
                body p {
                    margin: 25px 0 20px;
                    font-size: 14px;
                    line-height: 1.5; }
                a {
                    color: #21759B;
                    text-decoration: none; }
                a:hover {
                    color: #D54E21; }
            </style>
        </head>
        <body>
            <h1>Oops&hellip;</h1>
            <p>Hi there!  I'm just a plugin, not much I can do when called directly.</p>
            <p>Might be a good idea to call me from <a href="../../../wp-admin/options-general.php?page=kt_tinymce_color_grid">Dashboard &rsaquo; Settings &rsaquo; TinyMCE Color Grid</a>.</p>
        </body>
    </html>
    <?php
    exit;
}

if (!class_exists('kt_TinyMCE_Color_Grid')) {

    class kt_TinyMCE_Color_Grid {

        /**
         * Version number of plugin
         * @since 1.3
         * @var int
         */
        const VERSION = 132;

        /**
         * Enqueue minified scripts
         * @since 1.3.2
         * @var bool
         */
        const MINIFY = false;

        /**
         * Base key of plugin
         * @since 1.3.2
         * @var string
         */
        const KEY = 'kt_tinymce_color_grid';

        /**
         * Option key for storing custom color useage
         * @since 1.3.2
         * @var string
         */
        const CUSTOM = 'kt_color_grid_custom';

        /**
         * Option key for custom colors
         * @since 1.3.2
         * @var string
         */
        const SETS = 'kt_color_grid_sets';

        /**
         * Internal hash seed for security nonce
         * @since 1.3.2
         * @var string
         */
        const NONCE = 'kt-tinymce-color-grid-save-editor';

        /**
         * Holds luma transformations
         * @var array Array of floats as [-1..1]
         * @since 1.3
         */
        protected $lumas = array(-.75, -.60, -.44, -.28, -.14, -.05, 0, .22, .40, .57, .71, .82, .90);

        /**
         * Holds base RGBs
         * @var array Array of arrays of floats as [0..1]
         * @since 1.3
         */
        protected $colors = array(
            array(1, 0, 0), array(1, .47, 0), array(1, .85, 0),
            array(1, 1, 0), array(.85, 1, 0), array(.47, 1, 0),
            array(0, 1, 0), array(0, 1, .47), array(0, 1, .75),
            array(0, 1, 1), array(0, .85, 1), array(0, .47, 1),
            array(0, 0, 1), array(.47, 0, 1), array(.85, 0, 1),
            array(1, 0, 1), array(1, 0, .85), array(1, 0, .47)
        );

        /**
         * Holds translated names for $colors
         * @see kt_TinyMCE_Color_Grid::translate()
         * @var array Array of strings
         * @since 1.3
         */
        protected $names;

        /**
         * Holds a blueprint version of a color picker
         * @see kt_TinyMCE_Color_Grid::translate()
         * @var string
         * @since 1.3
         */
        protected $prototype;

        /**
         * Here we go ...
         *
         * Adds action and filter callbacks
         * @since 1.3
         */
        public function __construct() {
            add_action('plugins_loaded', array($this, 'textdomain'));
            add_action('admin_enqueue_scripts', array($this, 'scripts'));
            add_filter('tiny_mce_before_init', array($this, 'grid'));
            add_action('after_wp_tiny_mce', array($this, 'style'));
            add_action('admin_menu', array($this, 'menu'));
            add_filter('plugin_action_links', array($this, 'link'), 10, 2);
        }

        /**
         * Loads translation file
         * @since 1.3
         */
        public function textdomain() {
            load_plugin_textdomain('kt-tinymce-color-grid', false, dirname(plugin_basename(__FILE__)) . '/language');
            $this->translate();
        }

        /**
         * Translates some strings
         * @since 1.3.2
         */
        protected function translate() {
            $this->prototype = '<div class="picker" tabindex="2" aria-grabbed="false">
                        <span class="handle button hide-if-no-js dashicons-before dashicons-editor-ul" title="' . esc_attr__("Drag to sort", 'kt-tinymce-color-grid') . '"></span>
                        <button type="button" class="color button" tabindex="3" aria-haspopup="true" aria-controls="picker" aria-describedby="contextual-help-link" aria-label="' . esc_attr__('Color Picker', 'kt-tinymce-color-grid') . '">
                            <span class="preview" style="background-color:%1$s"></span>
                        </button>
                        <input class="hex" type="text" name="colors[]" tabindex="4" value="%1$s" placeholder="#RRGGBB" autocomplete="off" aria-label="' . esc_attr__('Hexadecimal Color', 'kt-tinymce-color-grid') . '" />
                        <input class="name" type="text" name="names[]" value="%2$s" tabindex="5" placeholder="' . esc_html__('Unnamed Color', 'kt-tinymce-color-grid') . '" aria-label="' . esc_attr__('Name of Color', 'kt-tinymce-color-grid') . '" />
                        <button type="submit" name="action" value="remove-%3$s" tabindex="6" class="remove button">
                            <i class="dashicons dashicons-trash"></i>
                            <span class="screen-reader-text">' . esc_html__('Delete', 'kt-tinymce-color-grid') . '</span>
                        </button>
                    </div>';
            $this->names = array(
                __('Red', 'kt-tinymce-color-grid'), __('Orange', 'kt-tinymce-color-grid'), __('Butter', 'kt-tinymce-color-grid'),
                __('Yellow', 'kt-tinymce-color-grid'), __('Lime', 'kt-tinymce-color-grid'), __('Grass', 'kt-tinymce-color-grid'),
                __('Green', 'kt-tinymce-color-grid'), __('Teal Sea', 'kt-tinymce-color-grid'), __('Aquamarine', 'kt-tinymce-color-grid'),
                __('Turquoise', 'kt-tinymce-color-grid'), __('Cornflower', 'kt-tinymce-color-grid'), __('Sky', 'kt-tinymce-color-grid'),
                __('Blue', 'kt-tinymce-color-grid'), __('Violet', 'kt-tinymce-color-grid'), __('Plum', 'kt-tinymce-color-grid'),
                __('Magenta', 'kt-tinymce-color-grid'), __('Pink', 'kt-tinymce-color-grid'), __('Raspberry', 'kt-tinymce-color-grid'),
            );
        }

        /**
         * Enqueues JavaScript and CSS files
         * @since 1.3
         * @param string $hook Current page load hook
         */
        public function scripts($hook) {
            switch ($hook) {
                case 'settings_page_' . self::KEY:
                    $min = self::MINIFY ? '.min' : '';
                    wp_enqueue_script(self::KEY, plugins_url("settings$min.js", __FILE__), array('jquery-ui-position', 'jquery-ui-sortable'), self::VERSION);
                    wp_enqueue_style(self::KEY, plugins_url('settings.css', __FILE__), array('farbtastic'), self::VERSION);
                    $prototype = preg_replace(array('/\s*\n\s*/', '/"/'), array('', "'"), $this->prototype);
                    wp_localize_script(self::KEY, 'kt_TinyMCE_prototype', $prototype);
                    break;
            }
        }

        /**
         * Adds dynamic CSS file for TinyMCE
         * @since 1.3
         */
        public function style() {
            $n = 0;
            if (get_option(self::CUSTOM, false)) {
                $n = ceil(count(get_option(self::SETS, array())) / count($this->lumas));
            }
            echo '<link rel="stylesheet" href="' . plugins_url('css.php', __FILE__) . "?n=$n&ver=" . self::VERSION . '" type="text/css" media="all" />';
        }

        /**
         * Adds entry for settings page to WordPress' admin menu
         * @since 1.3
         */
        public function menu() {
            $name = esc_html__('TinyMCE Color Grid', 'kt-tinymce-color-grid');
            $hook = add_options_page($name, $name, 'manage_options', self::KEY, array($this, 'editor'));
            add_action("load-$hook", array($this, 'save'));
            add_action("load-$hook", array($this, 'help'));
        }

        /**
         * Adds a link to the plugin listing page leading to the settings page
         * @since 1.3.2
         * @param array $links Array holding HTML
         * @param string $file Current name of plugin file
         * @return array Modified array
         */
        public function link($links, $file) {
            if (plugin_basename($file) == plugin_basename(__FILE__)) {
                $links[] = '<a href="options-general.php?page=' . self::KEY . '&custom=1" class="dashicons-before dashicons-admin-settings" title="' . esc_attr__('Opens the settings page for this plugin', 'kt-tinymce-color-grid') . '"> ' . esc_html__('Custom Colors', 'kt-tinymce-color-grid') . '</a>';
            }
            return $links;
        }

        /**
         * Adds help to settings page
         * @since 1.3.2
         */
        public function help() {
            $screen = get_current_screen();
            $screen->add_help_tab(array(
                'id' => 'aria',
                'title' => __('Accessibility', 'kt-tinymce-color-grid'),
                'content' => '
<p>' . __('You can access every input field of the editor via your keyboard.', 'kt-tinymce-color-grid') . '</p>
<p>' . __('The editor consists of a toolbar and a list of entries. The toolbar has a button <b>Add</b> for adding a new entry to the list. An entry has a color picker, two text fields — one holding a hexadecimal representation of the color, and one for the name of the entry — and lastly a button to remove the entry.', 'kt-tinymce-color-grid') . '</p>
<p>' . __('If an entry itself has focus you can change its position among its siblings by pressing the arrow keys <code>&uarr;</code> and <code>&darr;</code>. If you want to delete that entry press <code>DEL</code>', 'kt-tinymce-color-grid') . '</p>
<p>' . __('If a color picker has focus, use <code>&uarr;</code> and <code>&darr;</code> to change the lightness, <code>&larr;</code> and <code>&rarr;</code> to change the saturation, and <code>+</code> and <code>-</code> to change the hue. <code>ENTER</code> opens a visual color picker.', 'kt-tinymce-color-grid') . '</p>'
            ));
            $screen->set_help_sidebar('
<p><strong>' . esc_html__('Support') . '</strong></p>
<p><a href="https://wordpress.org/support/plugin/kt-tinymce-color-grid" target="_blank">' . esc_html__('Plugin Support') . '</a></p>
<p><strong>' . esc_html__('Color Values') . '</strong></p>
<p><ul>
    <li><a href="http://en.wikipedia.org/wiki/RGB">' . esc_html__('RGB Color Space') . '</a></li>
    <li><a href="http://en.wikipedia.org/wiki/HSL_and_HSV">' . esc_html__('HSL Color Space') . '</a></li>
</ul></p>');
        }

        /**
         * Renders settings page
         * @since 1.3
         * @global string $wp_version
         */
        public function editor() {
            global $wp_version;
            $version = 0;
            preg_match('/^(\d+\.\d+)/', $wp_version, $version);
            $head = floatval($version[1]) >= 4.3 ? 'h1' : 'h2';
            $use_custom = self::request('custom', get_option(self::CUSTOM, false));
            echo '
<div class="wrap">
    <' . $head . '>' . esc_html__('Settings') . ' › ' . esc_html__('TinyMCE Color Grid', 'kt-tinymce-color-grid') . '</' . $head . '>
    <div class="notice notice-warning hide-if-js"><p>
        <strong>' . esc_html__('This editor works best with JavaScript enabled.', 'kt-tinymce-color-grid') . '</strong>
    </p></div>
    <form action="options-general.php?page=' . self::KEY . '" method="post">
        ' . wp_nonce_field(self::NONCE, 'settings_nonce', false, false) . '
        <table class="form-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="custom" name="custom" tabindex="9" value="9"' . ($use_custom ? ' checked="checked"' : '') . ' aria-haspopup="true" aria-controls="editor" accesskey="' . esc_attr_x('C', 'accesskey for custom colors', 'kt-tinymce-color-grid') . '" />
                        <label for="custom">' . esc_html__("I'd like to define some custom colors", 'kt-tinymce-color-grid') . '</label>
                    </th>
                </tr>
            </thead>
            <tbody id="editor" aria-hidden="' . ($use_custom ? 'false' : 'true') . '">
                <tr>
                    <td id="toolbar" role="toolbar" aria-label="' . esc_attr__('Color Editor Toolbar', 'kt-tinymce-color-grid') . '">
                        <button id="add" type="submit" tabindex="8" name="action" value="add" class="button" aria-controls="colors" accesskey="' . esc_attr_x('A', 'accesskey for adding color', 'kt-tinymce-color-grid') . '">' . esc_html__('Add') . '</button>
                    </td>
                </tr>
                <tr>
                    <td id="colors" data-empty="' . esc_attr__('No colors yet', 'kt-tinymce-color-grid') . '">';
            $sets = get_option(self::SETS, array());
            foreach ($sets as $i => $set) {
                list($color, $name) = array_map('esc_attr', $set);
                printf($this->prototype, $color, $name, $i);
            }
            echo '</td>
                </tr>
            </tbody>
        </table>
        <p class="aubmit">
            <button type="submit" id="save" name="action" value="save" tabindex="9" class="button button-primary" accesskey="' . esc_attr_x('S', 'accesskey for saving', 'kt-tinymce-color-grid') . '">' . esc_html__('Save') . '</button>
        </p>
    </form>
</div>
<div id="picker" aria-hidden="true" aria-label="' . esc_attr__('Visual Color Picker', 'kt-tinymce-color-grid') . '"></div>';
        }

        /**
         * Saving routines
         * @since 1.3
         */
        public function save() {
            if (wp_verify_nonce(self::request('settings_nonce'), self::NONCE)) {
                $action = self::request('action');
                update_option(self::CUSTOM, self::request('custom', '0'));
                $colors = self::request('colors', array());
                $names = self::request('names', array());
                $sets = array();
                foreach ($names as $i => $name) {
                    $color = $this->sanitize($colors[$i]);
                    if ($color) {
                        $sets[] = array($color, sanitize_text_field(stripslashes($name)));
                    }
                }
                $m = false;
                if ($action == 'add') {
                    $sets[] = array('#000000', '');
                } else if (preg_match('/remove-(\d+)/', $action, $m)) {
                    array_splice($sets, $m[1], 1);
                }
                update_option(self::SETS, $sets);
                wp_redirect($action == 'save' ? add_query_arg('updated', '1') : remove_query_arg('updated'));
                exit;
            }
        }

        /**
         * Checks a string for a valid hexadecimal color and normalizes it to #RRGGBB
         * @since 1.3.2
         * @param string $string String to be checked
         * @return string|boolean Returns a color of #RRGGBB or false on failure
         */
        protected function sanitize($string) {
            $string = strtoupper($string);
            $match = null;
            if (preg_match('/([0-9A-F]{6}|[0-9A-F]{3})/', $string, $match)) {
                if (strlen($match[1]) == 3) {
                    return '#' . preg_replace('/[0-9A-F]/', '\1\1', $match[1]);
                }
                return '#' . $match[1];
            }
            return false;
        }

        /**
         * Renders the new color grid
         * @since 1.3
         * @param array $init Wordpress' TinyMCE inits
         * @return array
         */
        public function grid($init) {
            $rows = count($this->lumas);
            $grays = $rows - 2;
            $extra_cols = 0;
            if (get_option(self::CUSTOM, false)) {
                $sets = get_option(self::SETS, array());
                $extra_cols = ceil(count($sets) / $rows);
                $sets = array_chunk($sets, $rows);
                if ($extra_cols) {
                    $sets[$extra_cols - 1] = array_pad($sets[$extra_cols - 1], $rows, array('FFFFFF', ''));
                }
            }
            $map = array();
            foreach ($this->lumas as $row => $luma) {
                for ($column = 0; $column < $extra_cols; $column++) {
                    list($color, $name) = $sets[$column][$row];
                    $color = str_replace('#', '', $color);
                    $map[] = '"' . $color . '","' . esc_attr($name) . '"';
                }
                foreach ($this->colors as $column => $color) {
                    $name = $this->names[$column];
                    if ($luma < 0) {
                        $name = sprintf(__('%s (%d%% darker)', 'kt-tinymce-color-grid'), $name, $luma * -100);
                    } else if ($luma > 0) {
                        $name = sprintf(__('%s (%d%% brighter)', 'kt-tinymce-color-grid'), $name, $luma * 100);
                    }
                    $map[] = '"' . $this->hex($this->luma($color, $luma)) . '","' . esc_attr($name) . '"';
                }
                if ($row <= $grays) {
                    if ($row == 0) {
                        $name = __('Black', 'kt-tinymce-color-grid');
                    } else if ($row == $grays) {
                        $name = __('White', 'kt-tinymce-color-grid');
                    } else {
                        $name = sprintf(__('%d%% Gray', 'kt-tinymce-color-grid'), round(100 * $row / $grays));
                    }
                    $map[] = '"' . str_repeat($this->p2hex($row / $grays), 3) . '","' . esc_attr($name) . '"';
                }
            }
            $init['textcolor_map'] = '[' . implode(',', $map) . ']';
            $init['textcolor_cols'] = count($this->colors) + 1 + $extra_cols;
            $init['textcolor_rows'] = $rows;
            return $init;
        }

        /**
         * Converts a RGB component into its hexadecimal version
         * @since 1.3
         * @param float $p One RGB component of [0..1]
         * @return string The hexadecimal version of $p
         */
        protected function p2hex($p) {
            $s = dechex($p * 255);
            return (strlen($s) == 1 ? '0' : '') . $s;
        }

        /**
         * Converts a RGB into its hexadecimal version
         * @since 1.3
         * @param array $rgb RGB components a [0..1]
         * @return string The hexadecimal version as #RRGGBB
         */
        protected function hex($rgb) {
            return implode('', array_map(array($this, 'p2hex'), $rgb));
        }

        /**
         * Applies a luma to an RGB
         * @since 1.3
         * @param array $array RGB components of [0..1]
         * @param float $luma Luma value of [-1..1]
         * @return array The modified RGB
         */
        protected function luma($array, $luma) {
            if ($luma == 0) {
                return $array;
            }
            foreach ($array as &$p) {
                if ($luma < 0) {
                    $p = $p + $p * $luma;
                } else {
                    $p = max(0, min($p == 0 ? $luma : $p + (1 - $p) * $luma, 1));
                }
            }
            return $array;
        }

        /**
         * Fetches a HTTP request value
         * @since 1.3
         * @param string $key Name of the value to fetch
         * @param mixed|null $default Default value if $key does not exist
         * @return mixed The value for $key or $default
         */
        static function request($key, $default = null) {
            return key_exists($key, $_REQUEST) ? $_REQUEST[$key] : $default;
        }

    }

    $kt_TinyMCE_Color_Grid = new kt_TinyMCE_Color_Grid();
}
