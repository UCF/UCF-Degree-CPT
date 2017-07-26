<?php
/**
 * Commands for creating and upgrading degrees
 **/
class Degrees extends WP_CLI_Command {
	/**
	 * Imports degrees from the search service.
	 *
	 * ## OPTIONS
	 *
	 * <search_url>
	 * : The url of the search service you want to pull from. (Required)
	 *
	 * <catalog_url>
	 * : The url of the undergraduate catalog. (Required)
	 *
	 * ## EXAMPLES
	 *
	 * # Imports degrees from the dev search service.
	 * $ wp mainsite degrees import https://searchdev.smca.ucf.edu
	 *
	 * @when after_wp_load
	 */
	public function import( $args, $assoc_args ) {
		$search_url  = $args[0];
		$catalog_url = $args[1];
		$import = new UCF_Degree_Importer( $search_url, $catalog_url );
		try {
			$import->import();
		}
		catch( Exception $e ) {
			WP_CLI::error( $e->getMessage(), $e->getCode() );
		}
		WP_CLI::success( $import->get_stats() );
	}
}
