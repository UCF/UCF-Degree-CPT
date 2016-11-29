<?php
/**
 * Utility functions
 **/
if ( ! function_exists( 'ucf_degree_append_meta' ) ) {
	/**
	 * Appends the meta data of the degree to the $post object.
	 * @author Jim Barnes
	 * @since 0.0.1
	 * @param $post WP_POST | The WP_POST object
	 * @param WP_POST | The WP_POST object with the additional `meta` array attached as a field.
	 **/
	function ucf_degree_append_meta( $post ) {
		// Post meta
		$meta = get_post_meta( $post->ID );
		$post->meta = ucf_degree_reduce_meta_values( $meta );

		// Taxonomies + terms
		$taxonomies = UCF_Degree_PostType::taxonomies();
		$terms_by_tax = array_fill_keys( $taxonomies, array() );

		foreach ( $taxonomies as $tax ) {
			$terms = wp_get_post_terms( $post->ID, $tax );
			if ( !is_wp_error( $terms ) ) {
				$terms_by_tax[$tax] = $terms;
			}
		}

		$post->taxonomies = $terms_by_tax;

		return apply_filters( 'ucf_degree_append_meta', $post );
	}
}

if ( ! function_exists( 'ucf_degree_group_by_tax_term' ) ) {
	function ucf_degree_group_posts_by_tax( $taxonomy_slug, $posts ) {
		$retval = array();

		foreach( $posts as $post ) {
			$post_terms = wp_get_post_terms( $post->ID, $taxonomy_slug );

			foreach( $post_terms as $term ) {
				if ( ! is_array( $retval[$term->term_id] ) ) {
					$retval[$term->term_id] = array(
						'term'  => array(
							'name'  => $term->name,
							'meta' => ucf_degree_reduce_meta_values( get_term_meta( $term->term_id ) ),
						),
						'posts' => array()
					);
				}

				$retval[$term->term_id]['posts'][] = $post;
			}
		}

		return $retval;
	}
}

if ( ! function_exists( 'ucf_degree_reduce_meta_values' ) ) {
	/**
	 * Converts all single index arrays to values
	 * @author Jim Barnes
	 * @since 0.0.1
	 * @param $meta_array array | Array of meta values
	 * @return array
	 **/
	function ucf_degree_reduce_meta_values( $meta_array ) {
		$retval = $meta_array;

		foreach( $meta_array as $key=>$value ) {
			if ( is_array( $value ) && count( $value ) === 1 ) {
				$retval[$key] = $value[0];
			} else {
				$retval[$key] = $value;
			}
		}

		return $retval;
	}
}

/**
 * Joins term and meta tables to the default query.
 * NOTE: Meant for use with the below 'where_filter' and should be added and removed manually.
 * @author Jim Barnes
 * @since 0.0.1
 **/
if ( ! function_exists( 'ucf_degree_search_join_filter' ) ) {
	function ucf_degree_search_join_filter( $join='' ) {
		global $wpdb, $wp_query;

		if ( isset( $wp_query->query['degree_search'] ) || isset( $_GET['filters']['degree_search'] ) ) {
			$join .= " LEFT JOIN $wpdb->term_relationships as wtr ON ($wpdb->posts.ID = wtr.object_id)";
			$join .= " LEFT JOIN $wpdb->term_taxonomy as wtt ON (wtr.term_taxonomy_id = wtt.term_taxonomy_id)";
			$join .= " LEFT JOIN $wpdb->terms as wt ON (wtt.term_id = wt.term_id)";
			$join .= " left join $wpdb->postmeta as wpm ON ($wpdb->posts.ID = wpm.post_id)";
		}
		
		return $join;
	}

	add_filter( 'posts_join', 'ucf_degree_search_join_filter' );
}

if ( ! function_exists( 'ucf_degree_search_where_filter' ) ) {
	function ucf_degree_search_where_filter( $where='' ) {
		global $wpdb, $wp_query;

		if ( isset( $wp_query->query['degree_search'] ) || isset( $_GET['filters']['degree_search'] ) ) {
			$s = isset( $wp_query->query['degree_search'] ) ?: $_GET['filters']['degree_search'];
			$where = " AND post_type = 'degree' AND post_status = 'publish' AND (";
			$where .= $wpdb::prepare( " lower($wpdb->posts.post_title) LIKE %s OR", '%' . $s . '%' );
			$where .= $wpdb::prepare( " lower(wt.name) LIKE %s OR", '%' . $s . '%' );
			$where .= $wpdb::prepare( " lower(wpm.meta_value) LIKE %s)", '%'. $s . '%' );
		}

		return $where;
	}

	add_filter( 'posts_where', 'ucf_degree_search_where_filter' );
}

if ( ! function_exists( 'ucf_degree_search_groupby_filter' ) ) {
	function ucf_degree_search_groupby_filter( $groupby ) {
		global $wpdb, $wp_query;

		if ( isset( $wp_query->query['degree_search'] ) || isset( $_GET['filters']['degree_search'] ) ) {
			$groupby = "$wpdb->posts.ID";
		}

		return $groupby;
	}

	add_filter( 'posts_groupby', 'ucf_degree_search_groupby_filter' );
}

?>
