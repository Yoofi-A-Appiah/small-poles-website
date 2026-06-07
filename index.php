<?php
// WordPress requires index.php as the theme fallback.
// Redirect to the blog archive if accessed directly.
if ( ! is_front_page() ) {
    get_template_part( 'archive' );
} else {
    get_template_part( 'front-page' );
}
