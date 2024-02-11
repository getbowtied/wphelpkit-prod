/* global wp */

/**
 * Prevent our blocks from loading on certain post types.
 *
 * Which post types are prevented are control in the PHP
 * that decides whether to enqueue this JS.
 *
 * @since 0.0.5
 */
wp.blocks.unregisterBlockType('wphelpkit/search');
