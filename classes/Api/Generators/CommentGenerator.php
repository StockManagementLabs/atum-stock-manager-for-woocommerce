<?php
/**
 * Comment generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class CommentGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'comment';

	/**
	 * Prepare comment data according to schema
	 *
	 * @since 1.9.44
	 *
	 * @param array $comment Raw comment data.
	 *
	 * @return array Prepared comment data.
	 */
	protected function prepare_data( array $comment ): array {

		// Base data structure as per schema requirements.
		$prepared_data = [
			'id'           => (int) $comment['id'],
			'author'       => [
				'id'        => (int) $comment['author'],
				'_id'       => NULL,
				'name'      => $comment['author_name'] ?? '',
				'email'     => '',
				'avatar'    => '',
				'userAgent' => '',
			],
			'content'      => strip_tags( $comment['content']['rendered'] ?? '' ),
			'date'         => $comment['date'],
			'dateGMT'      => $comment['date_gmt'],
			'parent'       => $this->prepare_ids( $comment['parent'] ?? NULL ),
			'post'         => [
				'id'   => (int) $comment['post'],
				'_id'  => NULL,
				'type' => NULL, // TODO: DO WE NEED TO SET THIS?
			],
			'postType'     => NULL, // TODO: DO WE NEED TO SET THIS?
			'actionType'   => $this->extract_action_type( $comment['content']['rendered'] ),
			'status'       => $comment['status'],
			'type'         => $comment['type'],
			'addedByUser'  => FALSE,
			'customerNote' => FALSE,
			'metaData'     => [],
			'trash'        => FALSE,
			'conflict'     => FALSE,
		];

		// Add data array if needed based on content.
		$data = $this->extract_data_from_content( $comment['content']['rendered'] );
		if ( ! empty( $data ) ) {
			$prepared_data['data'] = $data;
		}

		return array_merge( $this->get_base_fields(), $prepared_data );

	}

	/**
	 * Extract action type from comment content
	 *
	 * @since 1.9.44
	 *
	 * @param string $content The comment content.
	 *
	 * @return string The action type.
	 */
	private function extract_action_type( string $content ): string {

		if ( str_contains( $content, 'Stock levels reduced' ) ) {
			return 'stock_reduced';
		}
		elseif ( str_contains( $content, 'Order status changed' ) ) {
			return 'status_changed';
		}
		elseif ( str_contains( $content, 'Added line items' ) ) {
			return 'line_items_added';
		}

		return 'note';

	}

	/**
	 * Extract structured data from comment content
	 *
	 * @since 1.9.44
	 *
	 * @param string $content The comment content.
	 *
	 * @return array The structured data.
	 */
	private function extract_data_from_content( string $content ): array {

		$data = [];

		// Extract stock level changes.
		// TODO: THIS IS PROBABLY WRONG, NEED TO FIX.
		if ( str_contains( $content, 'Stock levels reduced' ) ) {

			preg_match_all( '/([^(]+) \(([^)]+)\) (\d+)→(\d+)/', $content, $matches, PREG_SET_ORDER );

			foreach ( $matches as $match ) {
				$data[] = [
					'_id'          => 'stock_change:' . $this->generate_uuid(),
					'key'          => 'stock_change',
					'value'        => sprintf( '%d→%d', $match[3], $match[4] ),
					'displayKey'   => $match[1],
					'displayValue' => sprintf( 'Stock changed from %d to %d', $match[3], $match[4] ),
				];
			}

		}

		return $data;

	}

} 