<?php
/**
 * Plugin Name: Years Since - Timeless Texts
 * Plugin URI: https://github.com/bahiirwa/years-since/
 * Description: Keep date time related texts relevant. "I have worked for x years." becomes outdated within a year. This plugin enables your content to be timeless.
 * Version: 1.4.0
 * Author: Laurence Bahiirwa
 * Author URI: https://github.com/bahiirwa/years-since/
 * Tested up to: 6.5.2
 * Requires PHP: 7.4
 * Text Domain: years-since
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
namespace Laurencebahiirwa;

use \DateTime;
use function \number_format;

// Basic stop of brute force use.
defined('ABSPATH') || die('Unauthorized Access!');

/**
 * Implement the plugin. Let your post-time travel.
 */
class YearsSince {
    /**
     * A basic constructor.
     */
    public function init() {
        add_shortcode('years-since', array($this, 'shortcode_years_since'));
        add_shortcode('years-since-gb', array($this, 'shortcode_years_since_gb'));
        add_action('plugins_loaded',  array($this, 'load_plugin_text_domain'));
    }

    /**
     * Load gettext translate for text domain.
     *
     * @since 1.0.0
     *
     * @return void
     */
    function load_plugin_text_domain() {
        load_plugin_textdomain('years-since');
    }

    /**
     * A method to return the markup that replaces the shortcode.
     *
     * @param string $content
     *
     * @return string
     * @deprecated 1.3.5. The shortcode `years-since` works in the editor just fine. No need to wrap it.
     */
    public function shortcode_years_since_gb( string $content = '' ): string {
        return do_shortcode( $content );
    }

    /**
     * A method to return the markup that replaces the shortcode.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function shortcode_years_since( array $attributes, $today = '' ): string {

        $defaults = shortcode_atts( array(
            'html' => '',
            'text' => 'true',
        ), $attributes );

        if ( isset($attributes['y'])) {
            if (strlen($attributes['y']) !== 4) {
                return sprintf( '<p>%s</p>', esc_attr__('Year must be 4 digits.', 'years-since') );
            }

            // Bail if year is in the future.
            if ($attributes['y'] > date('Y')) {
                return sprintf( '<p>%s</p>', esc_attr__('Year cannot be greater than current year.', 'years-since') );
            }
        }

        if ( isset($attributes['m']) ) {
            if (strlen($attributes['m']) > 2) {
                return sprintf( '<p>%s</p>', esc_attr__('Month must be 2 digits.', 'years-since') );
            }

            if ( (int)$attributes['m'] > 12 ) {
                return sprintf( '<p>%s</p>', esc_attr__('Month should be a value less than 12.', 'years-since') );
            }
        }

        if ( isset($attributes['d']) ) {
            if (strlen($attributes['d']) > 2) {
                return sprintf( '<p>%s</p>', esc_attr__('Day must be 2 digits.', 'years-since') );
            }

            if ( (int)$attributes['d'] > 31 ) {
                return sprintf( '<p>%s</p>', esc_attr__('Days should be a value less than 31.', 'years-since') );
            }

            if ( (int)$attributes['m'] == 2 ) {
                if ( ((int)$attributes['y'] % 2 === 0) && (int)$attributes['d'] > 29 ) {
                    return sprintf( '<p>%s</p>',
                        esc_attr__( 'Days in Feb should be a value less than 29.', 'years-since' ) );
                }
                if ( ((int)$attributes['y'] % 2 > 0) && (int)$attributes['d'] > 28 ) {
                    return sprintf( '<p>%s</p>',
                        esc_attr__( 'Days in Feb should be a value less than 28.', 'years-since' ) );
                }
            }
        }

        // Cast the year value as an integer.
        $y = (isset($attributes['y']) && is_numeric($attributes['y'])) ? (int)$attributes['y'] : date('Y');

        // Ensure month and day values are integers, if set.
        $m = (isset($attributes['m']) && is_numeric($attributes['m'])) ? (int)$attributes['m'] : date('m');
        $d = (isset($attributes['d']) && is_numeric($attributes['d'])) ? (int)$attributes['d'] : date('d');

        if ( '' === $today ) {
            $today = new \DateTime();
        }
        $inputDate  = new \DateTime("$y-$m-$d"); // Returns'2023-10-15'
        $difference = date_diff($today,$inputDate);

        // If only the number is needed, return it here.
        if ( isset($attributes['text'] ) && 'false' === $attributes['text'] ) {
            $str = $difference->y;
            if ( '' !== $defaults['html'] ) {
                $str = '<' . $defaults['html'] . '>' . $str . '</' . $defaults['html'] . '>';
            }
            // $str = $difference->y;
            return $str;
        }

        // Compare the two dates using comparison methods.
        if ($inputDate > $today) {
            return sprintf( '<p>%s</p>', esc_attr__( 'Invalid date provided. Date cannot be greater than today.', 'years-since') );
        }

        // Return Weeks or days if less than a Week
        if ($difference->y < 1 && $difference->m < 1) {
            // Return Weeks
            if ($difference->d/7 > 1) {
                return $this->string_return($difference->d/7, __('week', 'years-since'), __('weeks', 'years-since'), $defaults );
            }
            // Return days if less than a Week
            return $this->string_return($difference->d, __('day', 'years-since'), __('days', 'years-since'), $defaults );
        }

        // Return Months
        if ($difference->y < 1) {
            return $this->string_return($difference->m, __('month', 'years-since'), __('months', 'years-since'), $defaults );
        }

        // Otherwise, return years
        if ($difference->y >= 1) {
            $str = $this->string_return($difference->y, __('year', 'years-since'), __('years', 'years-since'), $defaults );
            if ($difference->m > 0) {
                $str .= ' ' . $this->string_return($difference->m, __('month', 'years-since'), __('months', 'years-since'), $defaults );
            }
            if ($difference->d > 0) {
                $str .= ' ' . $this->string_return($difference->d, __('day', 'years-since'), __('days', 'years-since'), $defaults );
            }
            return $str;
        }

        return '';
    }

    /**
     * Translate the string.
     * Return Calculated time.
     */
    public function string_return($time, $singular, $plural, $defaults ): string {
        $str = sprintf(
            _n(
                '%d ' . $singular,
                '%d ' . $plural,
                $time,
                'years-since'
            ),
            number_format($time)
        );

        if ( '' !== $defaults['html'] ) {
            $str = '<' . $defaults['html'] . '>' . $str . '</' . $defaults['html'] . '>';
        }

        return $str;
    }
}

$years_since_init = new YearsSince();
$years_since_init->init();
