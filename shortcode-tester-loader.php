<?php

/*
Plugin Name: Classic Shortcode Tester
Plugin URI:  https://github.com/basic-tech/classic-shortcode-tester
Description: a simple shortcode tester for the TinyMCE editor
Version:     1.0
Author:      Basic Tech
Author URI:  https://github.com/basic-tech
License:     GPL2
*/

/*  
    Copyright 2013  Magenta Cuda

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

# The check for version is in its own file since if the file contains PHP 5.4 code an ugly fatal error will be triggered instead

list( $major, $minor ) = sscanf( phpversion(), '%D.%D' );
$tested_major = 5;
$tested_minor = 4;
if ( !( $major > $tested_major || ( $major == $tested_major && $minor >= $tested_minor ) ) ) {
    add_action( 'admin_notices', function () use ( $major, $minor, $tested_major, $tested_minor ) {
        echo <<<EOD
<div style="padding:10px 20px;border:2px solid red;margin:50px 20px;font-weight:bold;">
    &quot;Shortcode Tester&quot; will not work with PHP version $major.$minor;
    Please uninstall it or upgrade your PHP version to $tested_major.$tested_minor or later.
</div>
EOD;
    } );
    return;
}

# ok to start loading PHP 5.4 code

require_once( dirname( __FILE__ ) . '/shortcode-tester.php' );
