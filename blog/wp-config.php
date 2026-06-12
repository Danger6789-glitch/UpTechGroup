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
/** The name of the database for WordPress */
define( 'DB_NAME', 'c2761235c_wp479' );

/** Database username */
define( 'DB_USER', 'c2761235c_wp479' );

/** Database password */
define( 'DB_PASSWORD', 'J9!c.PpD@7(!Ss0G' );

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
define( 'AUTH_KEY',         'vhu2g0ixnnygksgsw1inluy7ydgfr8n4jpnpcjujsxhxijrv98anofjruvz70yio' );
define( 'SECURE_AUTH_KEY',  'i5dnpqxmkimpg848hx9fhltgszrkkcrn5hdwf65non8hxptrromxgdojtkb00gix' );
define( 'LOGGED_IN_KEY',    'etolagvbtwhuqywgu8ob9nybvdl1t3nfiz4xkgz958bnl4spvwnf4sivcvafpgu6' );
define( 'NONCE_KEY',        'nyijcgoyffnohdfchjmawtguy3sfybkodwegxy0eaqetwq8m0qnccekmwpgqvjfk' );
define( 'AUTH_SALT',        'mmytbdff5kesvpkrpefegjdcmzxd8vrocvjlwn6ztcrzgnazn07ufitv1z2oa3te' );
define( 'SECURE_AUTH_SALT', 'rcrouaidwg4faoj3fv11hgdstdw3er6fe3yiewzvm9wqdydic65ddpbqltpf9bm1' );
define( 'LOGGED_IN_SALT',   'enulilpdnlhgzygw3ps5scmnkpcnhchth6g7b9fs1e7cu1m0xdtztqroqmrfewpa' );
define( 'NONCE_SALT',       '0zvkdv0mmastrdsyja8nfwns3p3pzszf39qi9okve1jzxzv5i2mrgvthbmy0hhtq' );

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
$table_prefix = 'wp9p_';

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
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';