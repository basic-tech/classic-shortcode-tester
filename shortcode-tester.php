<?php

/*
 * Description:   A Shortcode Tester
 * Documentation: http://shortcodetester.wordpress.com/
 * Author:        Magenta Cuda
 * License:       GPL2
 */

/*  Copyright 2013  Magenta Cuda

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

/*
    Project IX: Shortcode Tester
    
    The Shortcode Tester is a post editor tool for WordPress developers that displays in a popup window the HTML generated by WordPress shortcodes,
    i.e. so you can quickly view the generated HTML without having to view the entire post. It is actually just the diagnostic part of Project III:
    A Tiny Post Content Template Interpreter. However, since it is generally useful I have separated into its own plugin.
*/

namespace mc_shortcode_tester {
        
    require_once( 'parse-functions.php' );

    define( 'START_OF_BODY',    '<!-- ##### ACTION:wp_body_open -->' );
    define( 'START_OF_CONTENT', '<!-- ##### FILTER:the_content -->' );   # This is the mark.
    define( 'START_OF_SIDEBAR', '<!-- ##### ACTION:get_sidebar -->' );
    define( 'START_OF_FOOTER',  '<!-- ##### ACTION:get_footer -->' );

    $construct = function( ) {

        if ( !is_admin( ) ) {
            
            # a 'template_redirect' handles evaluation of HTML fragments from post content editor shortcode tester
            # using a 'template_redirect' insures we have the correct context for evaluating shortcodes
            
            add_action( 'template_redirect', function( ) {
                global $post;
                if ( empty( $_GET[ 'mc-sct' ] ) || $_GET[ 'mc-sct' ] !== 'tpcti_eval_post_content' ) {
                    return;
                }
                if ( !wp_verify_nonce( $_REQUEST[ 'nonce' ], 'sct_ix-shortcode_tester_nonce' ) ) {
                    wp_nonce_ays( '' );
                }
                setup_postdata( $post );
                # instead of showing the post we evaluate the sent content in the context of the post
                $html = do_shortcode( stripslashes( $_REQUEST[ 'post_content' ] ) );
                if ( !empty( $_REQUEST[ 'prettify' ] ) && $_REQUEST[ 'prettify' ] === 'true' ) {
                    #$html = str_replace( ' ', '#', $html );
                    #$html = str_replace( "\t", 'X', $html );
                    $html = preg_replace( '#>\s+<#', '><', $html );
                    #echo $html;
                    #die;
                    # DOMDocument doesn't understand some HTML5 tags, e.g. figure so
                    libxml_use_internal_errors( TRUE );
                    $dom = new \DOMDocument( );
                    $dom->preserveWhiteSpace = FALSE;
                    $dom->loadHTML( $html );
                    $dom->normalizeDocument( );
                    $dom->formatOutput = TRUE;
                    # saveHTML( ) doesn't format but saveXML( ) does. Why? see http://stackoverflow.com/questions/768215/php-pretty-print-html-not-tidy
                    $html = $dom->saveXML( $dom->documentElement );
                    # remove the <html> and <body> elements that were added by saveHTML( )
                    $html = preg_replace( [ '#^.*<body>\r?\n#s', '#</body>.*$#s' ], '', $html );
                    #$html = str_replace( ' ', '#', $html );
                    #$html = str_replace( "\t", 'X', $html );
                }
                echo $html;
                die;
            } );   # add_action( 'template_redirect', function( ) {

        } else {
       
            # things to do only on post.php and post-new.php admin pages

            $post_editor_actions = function( ) {

                add_action( 'media_buttons', function( ) {
                   $nonce = wp_create_nonce( 'sct_ix-shortcode_tester_nonce' );
?>
<button class="button" type="button" id="sct_ix-shortcode-tester" data-nonce="<?php echo $nonce; ?>">Shortcode Tester</button>
<?php
                } );

                add_action( 'admin_enqueue_scripts', function( $hook ) {
                    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
                        return;
                    }
                    wp_enqueue_style(  'mf2tk_macros_admin', plugins_url( 'css/mf2tk_macros_admin.css', __FILE__ ) );
                    wp_enqueue_script( 'mf2tk_macros_admin', plugins_url(  'js/mf2tk_macros_admin.js',  __FILE__ ), [ 'jquery' ] );
                    wp_localize_script( 'mf2tk_macros_admin', 'mf2tk_macros_admin', [
                        'shortcode_tester_nonce' => wp_create_nonce( 'sct_ix-shortcode_tester_nonce' )
                    ] );
                } );

                # $shortcode_tester() outputs the HTML generated by WordPress shortcodes for the "Shortcode Tester" popup

                $shortcode_tester = function( ) {
?>
<!-- start shortcode tester -->
<div id="sct_ix-popup_margin" style="display:none;"></div>
<div id="mf2tk-shortcode-tester" class="sct_ix-popup" style="display:none;">
    <div class="sct_ix-heading">
        <h3>Shortcode Tester</h3>
        <button id="button-mf2tk-shortcode-tester-close">X</button>
    </div>
    <div class="sct_ix-instructions">
        Enter HTML and WordPress shortcodes in the Source text area.<br />
        Click the Evaluate button to display the generated HTML from WordPress shortcode processing in the Result text area.
    </div>
    <div class="sct_ix-button_bar">
        <button id="mf2tk-shortcode-tester-evaluate" class="mf2tk-shortcode-tester-button">Evaluate</button>
        <button id="mf2tk-shortcode-tester-evaluate-and-prettify" class="mf2tk-shortcode-tester-button">Evaluate & Prettify</button>
        <button id="mf2tk-shortcode-tester-show-source" class="mf2tk-shortcode-tester-button">Show Source Only</button>
        <button id="mf2tk-shortcode-tester-show-result" class="mf2tk-shortcode-tester-button">Show Result Only</button>
        <button id="mf2tk-shortcode-tester-show-both" class="mf2tk-shortcode-tester-button">Show Both</button>
        <button id="mf2tk-shortcode-tester-show-rendered" class="mf2tk-shortcode-tester-button">Show Rendered</button>
    </div>
    <div class="sct_ix-shortcode_tester_input_output">
        <div class="sct_ix-shortcode_tester_half">
            <div id="mf2tk-shortcode-tester-area-source" class="sct_ix-shortcode_tester_area">
                <h3>Source</h3>
                <textarea rows="12"></textarea>
            </div>
        </div>
        <div class="sct_ix-shortcode_tester_half">
            <div  id="mf2tk-shortcode-tester-area-result" class="sct_ix-shortcode_tester_area">
                <h3>Result</h3>
                <textarea rows="12" readonly></textarea>
            </div>
        </div>
    </div>
</div>
<!-- for the tooltip use the same classes as React uses for its tooltips -->
<div class="components-popover components-tooltip is-bottom is-center sct_ix-shortcode_tester_tooltip" style="display:none;">
    <div class="components-popover__content">Shortcode Tester</div>
</div>
<!-- end shortcode tester -->
<?php
                };   # $shortcode_tester = function( ) {

                # the "Insert Template" and "Shortcode Tester" are only injected on post.php and post-new.php admin pages
                add_action( 'admin_footer-post.php',     $shortcode_tester );
                add_action( 'admin_footer-post-new.php', $shortcode_tester );
                
            };   # $post_editor_actions = function( ) {
                
            add_action( 'load-post-new.php', $post_editor_actions );
            add_action( 'load-post.php',     $post_editor_actions );
            
        }   # if ( is_admin( ) ) {
            
    };   # $construct = function( ) {

    # hide_html_elements() hides top level HTML elements if the HTML element does not contain the mark.

    $hide_html_elements = function( $buffer, $start, $length, $mark = NULL, $is_fragment = FALSE ) {
        $elements = [ ];
        $n        = 0;
        # error_log( 'hide_html_elements():$length=' . $length );
        # error_log( 'hide_html_elements():substr( $buffer, $length - 16 ) = ' . substr( $buffer, $length - 16 ) );
        while ( ( $left_offset = \mc_html_parser\get_start_tag( $buffer, $start, $length ) ) !== FALSE ) {
            if ( ++$n > 1024 ) {
                # This should not happen. If it does probably a programming error causing an infinite loop.
                error_log( 'ERROR:hide_html_elements():Probably in an infinite loop.' );
                error_log( 'ERROR:hide_html_elements():                   $start = ' . $start );
                error_log( 'ERROR:hide_html_elements():substr( $buffer, $start ) = ' . substr( $buffer, $start ) );
                break;
            }
            # error_log( 'hide_html_elements():$start=' . $start );
            $right_offset = \mc_html_parser\get_name( $buffer, $left_offset + 1, $length );
            $name         = substr( $buffer, $left_offset + 1, $right_offset - $left_offset );
            # error_log( 'hide_html_elements():$name=' . $name );
            if ( ( $gt_offset = \mc_html_parser\get_greater_than( $buffer, $right_offset + 1, $length ) ) === FALSE ) {
                error_log( 'ERROR:hide_html_elements():Cannot find matching \'>\' for tag beginning with "' . substr( $buffer, $left_offset, 64 ) . '...' );
                break;
            }
            $marked       = FALSE;
            if ( ! in_array( $name, [ 'img', 'br', 'hr', 'p' ] ) ) {
                # Tag <name> should have a matching end tag </name>.
                # error_log( 'hide_html_elements():...>...=' . substr( $buffer, ( $gt_offset + 1 ) - 16, 64 ) );
                if ( ( $offset = \mc_html_parser\get_end_tag( $name, $buffer, $gt_offset + 1, $length ) ) === FALSE ) {
                    # This should only happen on malformed HTML, i.e. no matching end tag </tag>.
                    if ( $is_fragment ) {
                        # error_log( 'hide_html_elements():Cannot find matching end tag "</' . $name . '>".' );
                        # error_log( 'hide_html_elements(): HTML element begins with: "' . substr( $buffer, $left_offset, 64 ) . '..."' );
                        # However, if we are parsing a HTML fragment then this may not be an error as the fragment may not yet be complete.
                        # So, ignore this tag and continue.
                        $start = $gt_offset + 1;
                        continue;
                    }
                    error_log( 'ERROR:hide_html_elements():Cannot find matching end tag "</' . $name . '>".' );
                    error_log( 'ERROR:hide_html_elements(): HTML element begins with: "' . substr( $buffer, $left_offset, 64 ) . '..."' );
                    return FALSE;
                }
                # error_log( 'hide_html_elements():</tag>...=' . substr( $buffer, ( $offset + 1 ) - 16, 64 ) );
                if ( ! is_null( $mark ) ) {
                    # error_log( 'hide_html_elements():innerHTML = #####'
                    #     . substr( $buffer, $gt_offset + 1, ( $offset - ( strlen( $name ) + 1 ) ) - ( $gt_offset + 1 ) ) . '#####' );
                    if ( ( $marked = strpos( substr( $buffer, $gt_offset + 1, ( $offset - ( strlen( $name ) + 1 ) ) - ( $gt_offset + 1 ) ), $mark ) )
                            !== FALSE ) {
                        # TODO: Should also remove siblings of marked.
                        # TODO: But, the only sibling seems to be the title which can be easily removed in another way.
                    }
                }
            } else {   # if ( ! in_array( $name, [ 'img', 'br', 'hr', 'p' ] ) ) {
                $offset = $gt_offset;
            }
            if ( ! $marked && ! in_array( $name, [ 'script' ] ) ) {
                # Add element to list of elements to hide.
                $elements[ ] = (object) [ 'name' => $name, 'left' => $left_offset, 'right' => $gt_offset ];
            }
            $start = $offset + 1;
        }
        # Hide elements in reverse order so previous offsets are preserved.
        foreach ( array_reverse( $elements ) as $element ) {
            # error_log( 'hide_html_elements():$name=' . $element->name );
            # error_log( 'hide_html_elements():tag=' . substr( $buffer, $element->left, $element->right - ( $element->left - 1 ) ) );
            if ( ( $style_offset = strpos( substr( $buffer, $element->left, $element->right - ( $element->left - 1 ) ), 'style=' ) ) === FALSE ) {
                $buffer = substr_replace( $buffer, ' style="display:none;"', $element->right, 0 );
            } else {
                # Element already has an inline style attribute.
                // TODO:
            }
        }
        # error_log( 'hide_html_elements():return=' . "\n#####\n" . $buffer . "/n#####" );
        return $buffer;
    };

    $handle_output_buffering = function( $buffer, $caller, &$ob_state_stack ) {
        $ob_state             = end( $ob_state_stack );
        $hide_html_elements   = Output_Buffering_State::$hide_html_elements;
        $start_of_sidebar_len = strlen( START_OF_SIDEBAR );
        $start_of_footer_len  = strlen( START_OF_FOOTER );
        # error_log( 'handle_output_buffering():          $caller = ' . $caller );
        # error_log( 'handle_output_buffering():$ob_state->caller = ' . $ob_state->caller );
        # error_log( 'handle_output_buffering():$ob_state->ender  = ' . ( $ob_state->ender !== NULL ? $ob_state->ender
        #                                                                                           : 'end of execution' ) );
        # error_log( 'handle_output_buffering():$buffer=' . "\n#####\n" . $buffer . "\n#####" );
        if ( $caller === 'wp_body_open' ) {
            # ob_end_flush() can be called from multiple hooks - loop_end, get_sidebar, get_footer - or at the end of execution.
            # Hence, the buffer may or may not contain sidebars and/or the footer.
            if ( strpos( $buffer, START_OF_BODY ) !== 0 ) {
                error_log( 'ERROR:handle_output_buffering():unexpected start of body buffer, probably mismatched nested ob_start() output buffers.' );
                error_log( 'ERROR:handle_output_buffering():$buffer = "' . substr( $buffer, 64 ) );
            }
            $sidebar_offset = strpos( $buffer, START_OF_SIDEBAR );
            $footer_offset  = strpos( $buffer, START_OF_FOOTER );
            # $buffer contains a HTML fragment with embedded mark.
            $buffer = $hide_html_elements( $buffer, 0, $sidebar_offset !== FALSE ? $sidebar_offset
                                               : ( $footer_offset !== FALSE ? $footer_offset : strlen( $buffer ) ),
                                           START_OF_CONTENT, TRUE );
/*
 * Sidebars are now cleaned in an earlier call to ob_flush().
            $offset = 0;
            # N.B. There may be multiple sidebars.
            while ( ( $offset = strpos( $buffer, START_OF_SIDEBAR, $offset ) ) !== FALSE ) {
                $sidebar_offset = strpos( $buffer, START_OF_SIDEBAR, $offset + $start_of_sidebar_len );
                $footer_offset  = strpos( $buffer, START_OF_FOOTER,  $offset + $start_of_sidebar_len );
                $buffer = $hide_html_elements( $buffer, offset, $sidebar_offset !== FALSE ? $sidebar_offset
                                                   : ( $footer_offset !== FALSE ? $footer_offset : strlen( $buffer ) ) );
                $offset += $start_of_sidebar_len;
            }
 */
            # This buffer should no longer contain a footer.
            # if ( ( $offset = strpos( $buffer, START_OF_FOOTER ) ) !== FALSE ) {
            #     $buffer = $hide_html_elements( $buffer, $offset + strlen( START_OF_FOOTER ), strlen( $buffer ) );
            # }
            # error_log( 'handle_output_buffering():$caller=' . $caller );
            # error_log( 'handle_output_buffering():$return=' . "\n#####\n" . $buffer . "\n#####" );
        } else if ( $caller === 'get_sidebar' ) {
            if ( strpos( $buffer, START_OF_SIDEBAR ) !== 0 ) {
                error_log( 'ERROR:handle_output_buffering():unexpected start of sidebar buffer, probably mismatched nested ob_start() output buffers.' );
                error_log( 'ERROR:handle_output_buffering():$buffer = "' . substr( $buffer, 64 ) );
            }
            $start_offset = 0;
            # N.B. There may be multiple sidebars.
            while ( TRUE ) {
                $sidebar_offset = strpos( $buffer, START_OF_SIDEBAR, $start_offset + $start_of_sidebar_len );
                $content_offset = strpos( $buffer, START_OF_CONTENT, $start_offset + $start_of_sidebar_len );
                $footer_offset  = strpos( $buffer, START_OF_FOOTER,  $start_offset + $start_of_sidebar_len );
                $end_offset     = strlen( $buffer );
                foreach ( [ $sidebar_offset, $content_offset, $footer_offset ] as $offset ) {
                    if ( $offset !== FALSE  && $offset < $end_offset ) {
                        $end_offset = $offset;
                    }
                }
                $buffer = $hide_html_elements( $buffer, $start_offset, $end_offset, NULL, TRUE );
                if ( strlen( $buffer ) <= $start_of_sidebar_len
                    || ( $start_offset = strpos( $buffer, START_OF_SIDEBAR, $start_offset + $start_of_sidebar_len ) ) === FALSE ) {
                    break;
                }
            }
            # This buffer should no longer contain a footer.
            # if ( ( $offset = strpos( $buffer, START_OF_FOOTER ) ) !== FALSE ) {
            #     $buffer = $hide_html_elements( $buffer, $offset + start_of_footer_len, strlen( $buffer ) );
            # }
        } else if ( $caller === 'get_footer' ) {
            if ( strpos( $buffer, START_OF_FOOTER ) !== 0 ) {
                error_log( 'ERROR:handle_output_buffering():unexpected start of footer buffer, probably mismatched nested ob_start() output buffers.' );
                error_log( 'ERROR:handle_output_buffering():$buffer = "' . substr( $buffer, 64 ) );
            }
            $buffer = $hide_html_elements( $buffer, 0, strlen( $buffer ) );
        }
        # Reset $ob_state.
        array_pop( $ob_state_stack );
        return $buffer;
    };

    class Output_Buffering_State {
        public $on;
        public $caller;
        public $level;
        public $ender;
        public static $handle_output_buffering;
        public static $hide_html_elements;
        function __construct( $caller ) {
            $this->on     = TRUE;
            $this->caller = $caller;
            $this->level  = ob_get_level( );
            $this->ender  = NULL;
        }
    }
    Output_Buffering_State::$handle_output_buffering = $handle_output_buffering;
    Output_Buffering_State::$hide_html_elements      = $hide_html_elements;

   # $alt_template_redirect( ) will try to hide all HTML elements in the post content except the elements containing the mark.

    $alt_template_redirect = function( ) {
        # Using PHP's output buffering can be tricky since they can easily be incorrectly nested.
        # We must call ob_end_flush() only after all calls to ob_start() after our ob_start() have been matched.
        # $ob_state will have the state of our output buffering and have references to our output buffering handlers.
        $ob_state_stack = [ ];
/*
        add_action( 'get_header', function ( $name ) {
            echo "<!-- ##### ACTION:get_header -->\n";
        } );
        add_action( 'wp_head', function( ) {
            echo "<!-- ##### ACTION:wp_head -->\n";
        } );
        add_action( 'the_post', function( &$post, &$query ) {
            echo "<!-- ##### ACTION:the_post -->\n";
        }, 10, 2 );
 */
        add_filter( 'the_title', function( $title ) {
            return '';
        } );
        add_filter( 'the_content', function( $content ) {
            # Insert the mark into the post content and replace $content with $_REQUEST['post_content'].
            # This will evaluate $_REQUEST['post_content'] in the context of the post specified by the URL.
            # error_log( 'FILTER:the_content():$_REQUEST["post_content"] = ' . $_REQUEST['post_content'] );
            return START_OF_CONTENT . "\n" . stripslashes( $_REQUEST['post_content'] );
        }, 1 );
/*
        add_filter( 'the_content', function( $content ) {
            return $content;
        }, PHP_INT_MAX );
 */
        add_action( 'loop_end', function( &$query ) use ( &$ob_state_stack ) {
            while ( TRUE ) {
                $ob_state = empty( $ob_state_stack ) ? NULL : end( $ob_state_stack );
                if ( ! is_null( $ob_state ) && $ob_state->on && ob_get_level( ) === $ob_state->level ) {
                    # Clean previously emitted content and left sidebars.
                    # error_log( 'ACTION:loop_end():ob_end_flush()' );
                    $ob_state->ender = 'loop_end';
                    ob_end_flush( );
                } else {
                    break;
                }
            }
            # echo "<!-- ##### ACTION:loop_end -->\n";
        }, 10, 1 );
        add_action( 'wp_body_open', function( ) use ( &$ob_state_stack ) {
            ob_start( function( $buffer ) use ( &$ob_state_stack ) {
                $handle_output_buffering = Output_Buffering_State::$handle_output_buffering;
                return $handle_output_buffering( $buffer, 'wp_body_open', $ob_state_stack );
            } );
            array_push( $ob_state_stack, new Output_Buffering_State( 'wp_body_open' ) );
            echo START_OF_BODY . "\n";
        } );
        add_filter( 'get_edit_post_link', function ( $link ) {
            return '';
        } );
/*
        add_filter( 'bloginfo', function( $output, $show ) {
            echo "<!-- ##### FILTER:bloginfo:[[{$output}]] -->\n";
            return $output;
        }, 10, 2 );
        add_filter( 'has_nav_menu', function( $has_nav_menu, $location ) {
            return $has_nav_menu;
        }, 10, 2 );
 *
 * Dangerous to do this as this can insert a HTML comment inside a HTML tag.
 *
        foreach ( [ 'header_image' ] as $name ) {
            add_filter( "theme_mod_{$name}", function( $default ) use ( $name ) {
                echo "<!-- ##### FILTER:theme_mod_{$name}:[[{$default}]] -->\n";
                return $default;
            } );
        }
 */
        add_action( 'get_sidebar', function ( $name ) use ( &$ob_state_stack ) {
            # error_log( 'ACTION:get_sidebar():' );
            $ob_state = empty( $ob_state_stack ) ? NULL : end( $ob_state_stack );
            if ( ! is_null( $ob_state ) && $ob_state->on && ob_get_level( ) === $ob_state->level && $ob_state->caller === 'get_sidebar' ) {
                # Clean only a previously emitted sidebar.
                # error_log( 'ACTION:get_sidebar():ob_end_flush()' );
                $ob_state->ender = 'get_sidebar';
                ob_end_flush( );
            }
            ob_start( function( $buffer ) use ( &$ob_state_stack ) {
                $handle_output_buffering = Output_Buffering_State::$handle_output_buffering;
                return $handle_output_buffering( $buffer, 'get_sidebar', $ob_state_stack );
            } );
            array_push( $ob_state_stack, new Output_Buffering_State( 'get_sidebar' ) );
            echo START_OF_SIDEBAR . "\n";
        } );
        add_action( 'get_footer', function ( $name ) use ( &$ob_state_stack ) {
            while ( TRUE ) {
                $ob_state = empty( $ob_state_stack ) ? NULL : end( $ob_state_stack );
                if ( ! is_null( $ob_state ) && $ob_state->on && ob_get_level( ) === $ob_state->level ) {
                    # Clean previously emitted content and right sidebars (content should have been handled by ACTION 'loop_end').
                    # error_log( 'ACTION:get_footer():ob_end_flush()' );
                    $ob_state->ender = 'get_footer';
                    ob_end_flush( );
                } else {
                    break;
                }
            }
            $ob_state = empty( $ob_state_stack ) ? NULL : end( $ob_state_stack );
            if ( is_null( $ob_state ) || ! $ob_state->on ) {
                ob_start( function( $buffer ) use ( &$ob_state_stack ) {
                    $handle_output_buffering = Output_Buffering_State::$handle_output_buffering;
                    return $handle_output_buffering( $buffer, 'get_footer', $ob_state_stack );
               } );
                array_push( $ob_state_stack, new Output_Buffering_State( 'get_footer' ) );
                echo START_OF_FOOTER . "\n";
            }
        } );
/*
        add_action( 'wp_footer', function( ) {
            echo "<!-- ##### ACTION:wp_footer -->\n";
        } );
        register_shutdown_function( function( ) use ( &$ob_state_stack ) {
        } );
 */
    };   # $alt_template_redirect = function( ) {

    $construct( );

    if ( ! empty( $_GET[ 'mc-sct' ] ) && $_GET[ 'mc-sct' ] === 'tpcti_html_eval_post_content' ) {
        add_filter( 'show_admin_bar', function( $show_admin_bar ) {
            return FALSE;
        } );
        add_action( 'template_redirect', function( ) use ( $alt_template_redirect ) {
            $alt_template_redirect( );
        } );
    }

}   # namespace mc_shortcode_tester {
?>
