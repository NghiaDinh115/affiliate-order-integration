// Debug script - Add to functions.php temporarily
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<div style="position:fixed;bottom:0;right:0;background:#000;color:#fff;padding:10px;z-index:9999;">';
        echo '<strong>AOI Debug:</strong><br>';
        echo 'CTV Cookie: ' . (isset($_COOKIE['ctv']) ? $_COOKIE['ctv'] : 'NOT SET') . '<br>';
        echo 'URL ctv param: ' . (isset($_GET['ctv']) ? $_GET['ctv'] : 'NOT SET') . '<br>';
        echo '</div>';
    }
});
