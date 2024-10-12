<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbmfu0jxvwuztb' );

/** Database username */
define( 'DB_USER', 'ukdi4u8gndssz' );

/** Database password */
define( 'DB_PASSWORD', '5@g[R3F&ux4r' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         'B6FRX7$=Narp>b+Q>m~yxOjt$BenPoqrV`ZX.gn~DCe]Qj%YC^$Z`7r v9kE3j[j' );
define( 'SECURE_AUTH_KEY',  '5K%P4t~_1ys1%k,RD!@K[)&qzqXe{1Q[-hr|(O3LgcURqzfZPrB!]ek:cD!t?Q1J' );
define( 'LOGGED_IN_KEY',    '0SXNrr9?.=]qGXXxo/J]o2Cp9GWaos8/|/<FP;/>zeq7-D*7F[Y~?1OH)uWe2N4{' );
define( 'NONCE_KEY',        '{}Ot>{eex8x-I|27 pW`mK+Y{N) q.!,a&Wh8JPoO$f/!xwnd+ 3/f.:hii5o,%e' );
define( 'AUTH_SALT',        ':*b06:;filW%9uYv?w]S:2fNI<%iAUDtZuiyQXqzZiqPL[(|NYa%>NEJ&M(j#j?)' );
define( 'SECURE_AUTH_SALT', '~j%EqJB7R3n<{*F=nTaGkHkU;B8brY]1!7A<ut wzeNIrz}&r(i@vq4T6d.|x`60' );
define( 'LOGGED_IN_SALT',   'tzv6L[4fez#bBoeL!im6VE$-i=jXza3OU`K#(KFNd|f3<J24MIc=}g=]U2r#fbHJ' );
define( 'NONCE_SALT',       'IXG8u4zZ*ODS$BqVf{60:X{PwAiu`l/,Tr3xwrYu[:i>&kH~aE!fI+uLo=nWRt H' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );
/* Multisite 
define( 'WP_ALLOW_MULTISITE', false );
*/
/*(define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', 'vnxcargo.com.au' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
)
*/

/* Add any custom values between this line and the "stop editing" line. */

define( 'AS3CF_SETTINGS', serialize( array(
	'provider' => 'aws',
	'access-key-id' => 'AKIA4Z7K5PXJLLQBDLXX',
	'secret-access-key' => '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',
) ) );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
