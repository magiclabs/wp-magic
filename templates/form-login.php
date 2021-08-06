<?php
?>
<div id="magic">
    <form class="magic-form" onsubmit="handleLogin(event)">
        <label for="user_email">Email Address</label>
        <input type="email" id="user_email" name="email" required="required" placeholder="Enter your email" />
        <button type="submit">Send</button>
    </form>
</div>
<!-- From wp-login.php line 285-305 -->
<p id="backtoblog">
    <?php
    $html_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( home_url( '/' ) ),
        sprintf(
            /* translators: %s: Site title. */
            _x( '&larr; Go to %s', 'site' ),
            get_bloginfo( 'title', 'display' )
        )
    );
    /**
     * Filter the "Go to site" link displayed in the login page footer.
     *
     * @since 5.7.0
     *
     * @param string $link HTML link to the home URL of the current site.
     */
    echo apply_filters( 'login_site_html_link', $html_link );
    ?>
</p>