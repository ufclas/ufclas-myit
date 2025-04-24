<?php
/*
Plugin Name: UFCLAS MyIT
Plugin URI: https://it.clas.ufl.edu/
Description: Gravity Forms add-on that creates tickets in MyIT (Cherwell) from WordPress form submissions.
Version: 1.1.2
Author: Priscilla Chapman (CLAS IT)
Author URI: https://it.clas.ufl.edu/
License: GPL2
Build Date: 20170831
*/

define('UFCLAS_MYIT_VERSION', '1.1.2');

add_action('gform_loaded', array( 'UFCLAS_MyIT_Bootstrap', 'load' ), 5);

class UFCLAS_MyIT_Bootstrap
{

    public static function load()
    {

        if (! method_exists('GFForms', 'include_addon_framework') ) {
            return;
        }

        include_once 'inc/class-ufclasmyit.php';

        GFAddOn::register('UFCLASMyIT');
    }

}

function UFCLAS_MyIT()
{
    return UFCLASMyIT::get_instance();
}