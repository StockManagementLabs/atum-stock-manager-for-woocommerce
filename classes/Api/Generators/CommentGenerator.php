<?php
/**
 * Comment generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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
	 * @param array $comment Raw comment data
	 *
	 * @return array Prepared comment data
	 */
	protected function prepare_data( array $comment ): array {

		// Base data structure as per schema requirements
		$prepared_data = [
			'_id'          => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'         => $this->revision,
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'id'           => (int) $comment['id'],
			'author'       => [
				'id'        => (int) $comment['author'],
				'_id'       => 'user:' . $this->generate_uuid(),
				'name'      => $comment['author_name'],
				'email'     => '',
				'avatar'    => '',
				'userAgent' => '',
			],
			'content'      => strip_tags( $comment['content']['rendered'] ),
			'date'         => $comment['date'],
			'dateGMT'      => $comment['date_gmt'],
			'parent'       => [
				'id'  => (int) $comment['parent'],
				'_id' => $comment['parent'] ? 'comment:' . $this->generate_uuid() : NULL,
			],
			'post'         => [
				'id'   => (int) $comment['post'],
				'_id'  => 'order:' . $this->generate_uuid(),
				'type' => 'shop_order',
			],
			'postType'     => 'shop_order',
			'actionType'   => $this->extract_action_type( $comment['content']['rendered'] ),
			'status'       => $comment['status'],
			'type'         => $comment['type'],
			'addedByUser'  => FALSE,
			'customerNote' => FALSE,
			'metaData'     => [],
			'trash'        => FALSE,
			'conflict'     => FALSE,
		];

		// Add data array if needed based on content
		$data = $this->extract_data_from_content( $comment['content']['rendered'] );
		if ( !empty( $data ) ) {
			$prepared_data['data'] = $data;
		}

		return $prepared_data;
	}

	/**
	 * Extract action type from comment content
	 *
	 * @since 1.9.44
	 *
	 * @param string $content The comment content
	 *
	 * @return string The action type
	 */
	private function extract_action_type( string $content ): string {

		if ( strpos( $content, 'Stock levels reduced' ) !== FALSE ) {
			return 'stock_reduced';
		}
		if ( strpos( $content, 'Order status changed' ) !== FALSE ) {
			return 'status_changed';
		}
		if ( strpos( $content, 'Added line items' ) !== FALSE ) {
			return 'line_items_added';
		}

		return 'note';
	}

	/**
	 * Extract structured data from comment content
	 *
	 * @param string $content The comment content
	 * @return array The structured data
	 */
	private function extract_data_from_content( string $content ): array {

		$data = [];
		
		// Extract stock level changes
		if ( strpos( $content, 'Stock levels reduced' ) !== FALSE ) {
			preg_match_all( '/([^(]+) \(([^)]+)\) (\d+)→(\d+)/', $content, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$data[] = [
					'_id' => 'stock_change:' . $this->generate_uuid(),
					'key' => 'stock_change',
					'value' => sprintf( '%d→%d', $match[3], $match[4] ),
					'displayKey' => $match[1],
					'displayValue' => sprintf( 'Stock changed from %d to %d', $match[3], $match[4] ),
				];
			}
		}

		return $data;
	}

} 