<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //

# Ensure your wp-config.php has the correct database credentials
# Using the MySQL details you provided:
# DB_HOST: 178.128.112.23:3306
# DB_USER: livoadmin
# DB_PASSWORD: LIV0@#aeoer@

// define('DB_NAME', 'liveshopping');
// define('DB_USER', 'livoadmin');
// define('DB_PASSWORD', 'LIV0@#aeoer@');
// define('DB_HOST', '178.128.112.23:3306');

/** The name of the database for WordPress */
define( 'DB_NAME', 'liveshopping' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );


// Disable sessions for REST API requests
if (defined('REST_REQUEST') && REST_REQUEST) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies', '0');
    ini_set('session.use_trans_sid', '0');
    ini_set('max_execution_time', 120);
    ini_set('default_socket_timeout', 120);
    set_time_limit(120);
}

define('WP_CACHE', true);
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);

define('WP_MEMORY_LIMIT', '256M');

// Increase timeout for REST API
define('WP_HTTP_BLOCK_EXTERNAL', false);
define('WP_ACCESSIBLE_HOSTS', 'localhost,*.localhost');

// For Windows XAMPP specific fix
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

// Force REST API to use same domain
define('WP_SITEURL', 'http://localhost/liveshopping');
define('WP_HOME', 'http://localhost/liveshopping');

define('WP_ALLOW_REPAIR', true);
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'ho*Gl*v ,{wypfPFz:Fm8Bk|=tBL_o?=j^=2JcW:+)mG K;u6L9yU{5tFaN7`l&D' );
define( 'SECURE_AUTH_KEY',  '`;W4Z [fC@Uw%]=T,UY+ZHK_#}:zqiom,gcu+=L~=^mpQM{h7-?FcBNv%hn{Q0V|' );
define( 'LOGGED_IN_KEY',    'Uov2$-XV9$*Y/))cz?Ro{r6]-UszIu7iDy(:Rv.=6AcT.`A,v]>1>NoikJVPx>My' );
define( 'NONCE_KEY',        '!yw23O0*|@6>$T_5f5$CNNG[&J~uxdx@Hw2e+(aIRIRjsbD,^w^vOC9ZnqqR| qC' );
define( 'AUTH_SALT',        ';WG96q|SpjKUmj-qq>tChPX+!Qt]y<1h96 )aI%lO0RS/0[tbSMOrh~:Ekug.v1&' );
define( 'SECURE_AUTH_SALT', '/3tM;+B`)8Xk2,_[lj%}`@!p&wxI.>TdP~2r5y@ts/<MkXWcLJ6ZqTe*aqj$nPpa' );
define( 'LOGGED_IN_SALT',   'Hhl&xH^E`^>BxQie4_[hV#g9nU,=E}Ou(0js#H--EKzch.8t?<g!fj5Tw vT&12x' );
define( 'NONCE_SALT',       'm*l6s^trCD(<Nf Cwb;JZ<=;a{4n1Ew,wh|]E(VxGI2A;5@dOURe qV*1gzVezZV' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
