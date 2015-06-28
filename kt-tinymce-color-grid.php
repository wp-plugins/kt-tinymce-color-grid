<?php

/*
 * Plugin Name: TinyMCE Color Grid
 * Plugin URI: https://wordpress.org/plugins/kt-tinymce-color-grid
 * Description: Extends the TinyMCE Color Picker with a lot more colors to choose from.
 * Version: 1.3
 * Author: Daniel Schneider
 * Author URI: http://profiles.wordpress.org/kungtiger
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /language
 * Text Domain: kt-tinymce-color-grid
 */

if (!class_exists('kt_TinyMCE_Color_Grid')) {

    class kt_TinyMCE_Color_Grid {

        const VERSION = 1.3;

        protected $dir;
        protected $url;
        protected $blueprint;
        protected $lumas;
        protected $colors;
        protected $names;

        public function __construct() {
            $this->dir = plugin_basename(dirname(__FILE__));
            $this->url = plugins_url() . '/' . $this->dir;
            
            add_action('plugins_loaded', array($this, 'l10n'));
            add_action('admin_enqueue_scripts', array($this, 'scripts'));
            add_action('after_wp_tiny_mce', array($this, 'style'));
            add_filter('tiny_mce_before_init', array($this, 'grid'));
            add_action('admin_menu', array($this, 'menu'));

            $this->blueprint = '<div class="color">
                <span class="picker button" title="' . __('Click here to choose a color from a color picker', 'kt-tinymce-color-grid') . '">
                    <span class="preview"></span>
                </span>
                <div class="farbtastic" style="display:none"></div>
                <input class="hex" type="text" name="colors[]" value="%1$s" />
                <input class="name" type="text" name="names[]" value="%2$s"/>
                <button type="button" class="remove button dashicons-before dashicons-no" title="' . __('Remove this color', 'kt-tinymce-color-grid') . '"></button>
            </div>';
            $this->colors = array(
                array(1, 0, 0), array(1, .47, 0), array(1, .85, 0),
                array(1, 1, 0), array(.85, 1, 0), array(.47, 1, 0),
                array(0, 1, 0), array(0, 1, .47), array(0, 1, .75),
                array(0, 1, 1), array(0, .85, 1), array(0, .47, 1),
                array(0, 0, 1), array(.47, 0, 1), array(.85, 0, 1),
                array(1, 0, 1), array(1, 0, .85), array(1, 0, .47)
            );
            $this->names = array(
                __('Red', 'kt-tinymce-color-grid'), __('Orange', 'kt-tinymce-color-grid'), __('Butter', 'kt-tinymce-color-grid'),
                __('Yellow', 'kt-tinymce-color-grid'), __('Lime', 'kt-tinymce-color-grid'), __('Grass', 'kt-tinymce-color-grid'),
                __('Green', 'kt-tinymce-color-grid'), __('Teal Sea', 'kt-tinymce-color-grid'), __('Aquamarine', 'kt-tinymce-color-grid'),
                __('Turquoise', 'kt-tinymce-color-grid'), __('Cornflower', 'kt-tinymce-color-grid'), __('Sky', 'kt-tinymce-color-grid'),
                __('Blue', 'kt-tinymce-color-grid'), __('Violet', 'kt-tinymce-color-grid'), __('Plum', 'kt-tinymce-color-grid'),
                __('Magenta', 'kt-tinymce-color-grid'), __('Pink', 'kt-tinymce-color-grid'), __('Raspberry', 'kt-tinymce-color-grid'),
            );
            $this->lumas = array(-.75, -.60, -.44, -.28, -.14, -.05, 0, .22, .40, .57, .71, .82, .90);
        }

        public function l10n() {
            load_plugin_textdomain('kt-tinymce-color-grid', false, dirname(plugin_basename(__FILE__)) . '/language');
        }

        public function scripts($hook) {
            switch ($hook) {
                case 'settings_page_kt_tinymce_color_grid':
                    wp_enqueue_script('farbtastic');
                    wp_enqueue_style('farbtastic');
                    wp_enqueue_script('kt-tinymce_color_grid', plugins_url('kt-tinymce-color-grid.js', __FILE__), array('jquery'), self::VERSION);
                    wp_enqueue_style('kt-tinymce_color_grid', plugins_url('css/settings.css', __FILE__), null, self::VERSION);
                    wp_localize_script('kt-tinymce_color_grid', 'kt_TinyMCE_blueprint', $this->blueprint);
                    break;
            }
        }

        public function menu() {
            $hook = add_options_page(__('Settings') . ' › TinyMCE Color Grid', 'TinyMCE Color Grid', 'manage_options', 'kt_tinymce_color_grid', array($this, 'settings'));
            add_action('load-' . $hook, array($this, 'save_settings'));
        }

        public function settings() {
            $use_custom = get_option('kt_color_grid_custom', '0');
            $sets = get_option('kt_color_grid_sets', array());
            $html = '';
            foreach ($sets as $set) {
                list($color, $name) = $set;
                $html .= sprintf('
            ' . $this->blueprint, $color, $name);
            }
            echo '
<div class="wrap"><h2>' . __('Settings') . ' › TinyMCE Color Grid</h2>
    <form action="" method="post">
        ' . wp_nonce_field('kt-tinymce-color-grid-save-settings', 'settings_nonce', false, false) . '
        <table class="form-table">
            <tbody>
                <tr>
                    <th><input type="checkbox" id="checkbox_custom" name="custom" value="yes"' . checked($use_custom, '1', false) . ' />
                        <label for="checkbox_custom">' . __("I'd like to define some custom colors", 'kt-tinymce-color-grid') . '</label></th>
                </tr>
            </tbody>
        </table>
        <div id="custom_colors"' . ($use_custom ? '' : ' style="display: none"') . '>' . $html . '
            <button id="add_custom_color" type="button" class="button button dashicons-before dashicons-plus">' . __('Add') . '</button>
        </div>
        <button type="submit" name="action" value="save-settings" class="button button-primary">' . __('Save') . '</button>
    </form>
</div>';
        }

        public function save_settings() {
            if (self::request('action') == 'save-settings') {
                if (wp_verify_nonce(self::request('settings_nonce'), 'kt-tinymce-color-grid-save-settings')) {
                    update_option('kt_color_grid_custom', (int) !!self::request('custom'));
                    $colors = self::request('colors');
                    $names = self::request('names');
                    $sets = array();
                    foreach ($names as $i => $name) {
                        $sets[] = array($colors[$i], $name);
                    }
                    update_option('kt_color_grid_sets', $sets);
                    $goback = add_query_arg('updated', '1', wp_get_referer());
                    wp_redirect($goback);
                    exit;
                }
            }
        }

        public function style() {
            $n = ceil(count(get_option('kt_color_grid_sets', array())) / count($this->lumas));
            echo '<link rel="stylesheet" id="kt_tinymce_color_grid_css" href="' . plugins_url('kt-tinymce-color-grid-style.php', __FILE__) . '?n=' . $n . '&ver=' . self::VERSION . '" type="text/css" media="all" />';
        }

        public function grid($init) {
            $rows = count($this->lumas);
            $grays = $rows - 2;
            $step = 1 / $grays;

            $sets = get_option('kt_color_grid_sets', array());
            $sets_count = count($sets);
            $extra_cols = ceil($sets_count / $rows);

            $max_sets = $extra_cols * $rows;
            if ($max_sets > $sets_count) {
                $empty_count = $max_sets - $sets_count;
                $empties = array_fill(0, $empty_count, array('FFFFFF', ''));
                $sets = array_merge($sets, $empties);
            }

            $map = array();
            foreach ($this->lumas as $row => $luma) {
                for ($c = 0; $c < $extra_cols; $c++) {
                    list($color, $name) = $sets[$c * $rows + $row];
                    $color = str_replace('#', '', $color);
                    $map[] = '"' . $color . '","' . $name . '"';
                }
                foreach ($this->colors as $col => $color) {
                    $name = $this->names[$col];
                    if ($luma < 0) {
                        $hint = sprintf(__('%s (%d%% darker)', 'kt-tinymce-color-grid'), $name, $luma * -100);
                    } else if ($luma > 0) {
                        $hint = sprintf(__('%s (%d%% brighter)', 'kt-tinymce-color-grid'), $name, $luma * 100);
                    } else {
                        $hint = $this->names[$col];
                    }
                    $map[] = '"' . $this->hex($this->luma($color, $luma)) . '","' . $hint . '"';
                }
                if ($row <= $grays) {
                    if ($row == 0) {
                        $name = __('Black', 'kt-tinymce-color-grid');
                    } else if ($row == $grays) {
                        $name = __('White', 'kt-tinymce-color-grid');
                    } else {
                        $name = sprintf(__('%d%% Gray', 'kt-tinymce-color-grid'), round(100 * $row / $grays));
                    }
                    $map[] = '"' . str_repeat($this->p2hex($step * $row), 3) . '","' . $name . '"';
                }
            }
            $init['textcolor_map'] = '[' . implode(',', $map) . ']';
            $init['textcolor_cols'] = count($this->colors) + 1 + $extra_cols;
            $init['textcolor_rows'] = $rows;
            return $init;
        }

        protected function p2hex($p) {
            $s = dechex($p * 255);
            return (strlen($s) == 1 ? '0' : '') . $s;
        }

        protected function hex($a) {
            return implode('', array_map(array($this, 'p2hex'), $a));
        }

        protected function luma($a, $l) {
            if ($l == 0) {
                return $a;
            }
            if ($l < 0) {
                $m = array(
                    $a[0] + $a[0] * $l,
                    $a[1] + $a[1] * $l,
                    $a[2] + $a[2] * $l
                );
            } else {
                $m = array();
                foreach ($a as $i => $c) {
                    $m[$i] = max(0, min($c == 0 ? $l : $c + (1 - $c) * $l, 1));
                }
            }
            return $m;
        }

        static function request($key, $default = null) {
            return key_exists($key, $_REQUEST) ? $_REQUEST[$key] : $default;
        }

    }

    $kt_TinyMCE_Color_Grid = new kt_TinyMCE_Color_Grid();
}
