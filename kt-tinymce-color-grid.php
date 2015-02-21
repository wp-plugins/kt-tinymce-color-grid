<?php

/*
 * Plugin Name: TinyMCE Bigger Color Grid
 * Plugin URI: https://wordpress.org/plugins/kt-tinymce-color-grid
 * Description: Extends the TinyMCE Color Picker with a lot more colors to choose from.
 * Version: 1.0
 * Author: Daniel Schneider
 * Author URI: http://profiles.wordpress.org/kungtiger
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /language
 * Text Domain: kt-tinymce-color-grid
 */

add_action('plugins_loaded', 'kt_tinymce_color_grid_textdomain');
add_action('admin_enqueue_scripts', 'kt_tinymce_color_grid_style');
add_filter('tiny_mce_before_init', 'kt_tinymce_color_grid');

function kt_tinymce_color_grid_textdomain() {
    load_plugin_textdomain('kt-tinymce-color-grid', false, dirname(plugin_basename(__FILE__)) . '/language');
}

function kt_tinymce_color_grid_style() {
    wp_enqueue_style('kt-tinymce-color-grid', plugins_url('kt-tinymce-color-grid.css', __FILE__));
}

function kt_tinymce_color_grid($init) {

    function p2hex($p) {
        $s = dechex($p * 255);
        return (strlen($s) == 1 ? '0' : '') . $s;
    }

    function hex($a) {
        return implode('', array_map('p2hex', $a));
    }

    function luma($a, $l) {
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

    $colors = array(
        array(1, 0, 0), array(1, .47, 0), array(1, .85, 0),
        array(1, 1, 0), array(.85, 1, 0), array(.47, 1, 0),
        array(0, 1, 0), array(0, 1, .47), array(0, 1, .75),
        array(0, 1, 1), array(0, .85, 1), array(0, .47, 1),
        array(0, 0, 1), array(.47, 0, 1), array(.85, 0, 1),
        array(1, 0, 1), array(1, 0, .85), array(1, 0, .47)
    );
    $lumas = array(-.75, -.60, -.44, -.28, -.14, -.05, 0, .22, .40, .57, .71, .82, .90);
    $names = array(
        __('Red', 'kt-tinymce-color-grid'), __('Orange', 'kt-tinymce-color-grid'), __('Butter', 'kt-tinymce-color-grid'),
        __('Yellow', 'kt-tinymce-color-grid'), __('Lime', 'kt-tinymce-color-grid'), __('Grass', 'kt-tinymce-color-grid'),
        __('Green', 'kt-tinymce-color-grid'), __('Teal Sea', 'kt-tinymce-color-grid'), __('Aquamarine', 'kt-tinymce-color-grid'),
        __('Turquoise', 'kt-tinymce-color-grid'), __('Cornflower', 'kt-tinymce-color-grid'), __('Sky', 'kt-tinymce-color-grid'),
        __('Blue', 'kt-tinymce-color-grid'), __('Violet', 'kt-tinymce-color-grid'), __('Plum', 'kt-tinymce-color-grid'),
        __('Magenta', 'kt-tinymce-color-grid'), __('Pink', 'kt-tinymce-color-grid'), __('Raspberry', 'kt-tinymce-color-grid'),
    );
    $rows = count($lumas);
    $step = 1 / ($rows - 2);
    $map = array();
    foreach ($lumas as $i => $luma) {
        foreach ($colors as $j => $color) {
            $name = $names[$j];
            if ($luma < 0) {
                $hint = sprintf(__('%s (%d%% darker)', 'kt-tinymce-color-grid'), $name, $luma * -100);
            } else if ($luma > 0) {
                $hint = sprintf(__('%s (%d%% brighter)', 'kt-tinymce-color-grid'), $name, $luma * 100);
            } else {
                $hint = $names[$j];
            }
            $map[] = '"' . hex(luma($color, $luma)) . '","' . $hint . '"';
        }
        if ($i == 0) {
            $name = __('Black', 'kt-tinymce-color-grid');
        } else if ($i == $rows - 2) {
            $name = __('White', 'kt-tinymce-color-grid');
        } else {
            $name = sprintf(__('%d%% Gray', 'kt-tinymce-color-grid'), round(100 * $i / ($rows - 2)));
        }
        $map[] = '"' . str_repeat(p2hex($step * $i), 3) . '","' . $name . '"';
    }
    array_pop($map);
    $init['textcolor_map'] = '[' . implode(',', $map) . ']';
    $init['textcolor_cols'] = count($colors) + 1;
    $init['textcolor_rows'] = $rows;
    return $init;
}
