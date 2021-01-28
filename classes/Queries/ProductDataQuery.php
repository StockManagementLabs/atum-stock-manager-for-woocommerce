<?php
/**
 * Custom meta query for ATUM product data.
 * This class is based on the \WP_Meta_Query class.
 *
 * @package         Atum\Queries
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Queries;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;

class ProductDataQuery extends \WP_Meta_Query {

	/**
	 * Generates SQL clauses to be appended to a main query.
	 *
	 * @since 1.5.0
	 *
	 * @param string $type              Optional. Type of meta.
	 * @param string $primary_table     Database table where the object being filtered is stored (eg wp_users).
	 * @param string $primary_id_column ID column for the filtered object in $primary_table.
	 * @param object $context           Optional. The main query object.
	 *
	 * @return false|array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql( $type = '', $primary_table = '', $primary_id_column = '', $context = NULL ) {

		global $wpdb;

		$this->table_aliases = array();

		$type                    = $type ?: Globals::ATUM_PRODUCT_DATA_TABLE;
		$this->meta_table        = $wpdb->prefix . $type;
		$this->meta_id_column    = 'product_id';
		$this->primary_table     = $primary_table ?: $wpdb->posts;
		$this->primary_id_column = $primary_id_column ?: 'ID';

		$sql = $this->get_sql_clauses();

		/*
		 * If any JOINs are LEFT JOINs (as in the case of NOT EXISTS), then all JOINs should
		 * be LEFT. Otherwise posts with no metadata will be excluded from results.
		 */
		if ( FALSE !== strpos( $sql['join'], 'LEFT JOIN' ) ) {
			$sql['join'] = str_replace( 'INNER JOIN', 'LEFT JOIN', $sql['join'] );
		}

		/**
		 * Filters the ATUM product data query's generated SQL.
		 *
		 * @param array  $clauses           Array containing the query's JOIN and WHERE clauses.
		 * @param array  $queries           Array of product data queries.
		 * @param string $primary_table     Primary table.
		 * @param string $primary_id_column Primary column ID.
		 * @param object $context           The main query object.
		 */
		return apply_filters_ref_array( 'atum/product_data_query/get_meta_sql', array( $sql, $this->queries, $primary_table, $primary_id_column, $context ) );

	}

	/**
	 * Generate SQL JOIN and WHERE clauses for a first-order query clause.
	 *
	 * "First-order" means that it's an array with a 'key' or 'value'.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $clause       Query clause (passed by reference).
	 * @param array  $parent_query Parent query array.
	 * @param string $clause_key   Optional. The array key used to name the clause in the original `$meta_query`
	 *                             parameters. If not provided, a key will be generated automatically.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) {

		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join'  => array(),
		);

		if ( isset( $clause['compare'] ) ) {
			$clause['compare'] = strtoupper( $clause['compare'] );
		}
		else {
			$clause['compare'] = isset( $clause['value'] ) && is_array( $clause['value'] ) ? 'IN' : '=';
		}

		if ( ! in_array( $clause['compare'], array(
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'BETWEEN',
			'NOT BETWEEN',
			'EXISTS',
			'NOT EXISTS',
			'REGEXP',
			'NOT REGEXP',
			'RLIKE',
		) ) ) {
			$clause['compare'] = '=';
		}

		$meta_compare = $clause['compare'];

		// First build the JOIN clause, if one is required.
		$join = '';

		// We prefer to avoid joins if possible. Look for an existing join compatible with this clause.
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );
		if ( FALSE === $alias ) {

			$i     = count( $this->table_aliases );
			$alias = $i ? 'apd' . $i : $this->meta_table;
			$join .= " INNER JOIN $this->meta_table";
			$join .= $i ? " AS $alias" : '';
			$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][]  = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type     = isset( $clause['type'] ) ? $clause['type'] : '';
		$meta_type      = $this->get_cast_for_type( $_meta_type );
		$clause['cast'] = $meta_type;

		// Fallback for clause keys is the table alias. Key must be a string.
		if ( is_int( $clause_key ) || ! $clause_key ) {
			$clause_key = $clause['alias'];
		}

		// Ensure unique clause keys, so none are overwritten.
		$iterator        = 1;
		$clause_key_base = $clause_key;

		while ( isset( $this->clauses[ $clause_key ] ) ) {
			$clause_key = $clause_key_base . '-' . $iterator;
			$iterator++;
		}

		// Store the clause in our flat array.
		$this->clauses[ $clause_key ] =& $clause;

		// Build the WHERE clause.
		if ( array_key_exists( 'value', $clause ) ) {

			$meta_value = $clause['value'];

			if ( in_array( $meta_compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) {

				if ( ! is_array( $meta_value ) ) {
					$meta_value = preg_split( '/[,\s]+/', $meta_value );
				}

			}
			else {
				$meta_value = trim( $meta_value );
			}

			switch ( $meta_compare ) {
				case 'IN':
				case 'NOT IN':
					$meta_compare_string = '(' . substr( str_repeat( ',%s', count( $meta_value ) ), 1 ) . ')';
					$where               = $wpdb->prepare( $meta_compare_string, $meta_value ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					break;

				case 'BETWEEN':
				case 'NOT BETWEEN':
					$meta_value = array_slice( $meta_value, 0, 2 );
					$where      = $wpdb->prepare( '%s AND %s', $meta_value ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
					break;

				case 'LIKE':
				case 'NOT LIKE':
					$meta_value = '%' . $wpdb->esc_like( $meta_value ) . '%';
					$where      = $wpdb->prepare( '%s', $meta_value );
					break;

				// EXISTS with a value is interpreted as '='.
				case 'EXISTS':
					$meta_compare = '=';
					$where        = $wpdb->prepare( '%s', $meta_value );
					break;

				// 'value' is ignored for NOT EXISTS.
				case 'NOT EXISTS':
					$where = '';
					break;

				default:
					$where = $wpdb->prepare( '%s', $meta_value );
					break;

			}

			if ( $where ) {

				if ( 'CHAR' === $meta_type ) {
					$sql_chunks['where'][] = "$alias.{$clause['key']} {$meta_compare} {$where}";
				}
				else {
					$sql_chunks['where'][] = "CAST($alias.{$clause['key']} AS {$meta_type}) {$meta_compare} {$where}";
				}

			}

		}

		/*
		 * Multiple WHERE clauses (for meta_key and meta_value) should
		 * be joined in parentheses.
		 */
		if ( 1 < count( $sql_chunks['where'] ) ) {
			$sql_chunks['where'] = array( '( ' . implode( ' AND ', $sql_chunks['where'] ) . ' )' );
		}

		return $sql_chunks;

	}

}
