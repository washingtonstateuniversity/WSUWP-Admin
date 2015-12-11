<?php
/**
 * Implements spine_option command.
 */
class WSU_CLI_Spine_Option extends WP_CLI_Command {

	/**
	 * Prints the value of a Spine option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The spine option to retrieve.
	 *
	 * ## EXAMPLES
	 *
	 *     wp spine_option get grid_style
	 *
	 * @synopsis <name>
	 */
	function get( $args, $assoc_args ) {
		list( $option ) = $args;

		if ( function_exists( 'spine_get_option' ) ) {
			$value = spine_get_option( $option );
		} else {
			$value = 'invalid';
		}

		WP_CLI::line( "$value" );
	}
}

WP_CLI::add_command( 'spine_option', 'WSU_CLI_Spine_Option' );