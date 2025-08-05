<?php
/**
 * @todo    add description
 *
 * @package BuddyBoss\Search
 * @since   BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Members' ) ) :

	/**
	 * BuddyPress Global Search  - search members class
	 */
	class Bp_Search_Members extends Bp_Search_Type {
		private $type = 'members';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @return object Bp_Search_Members
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new Bp_Search_Members();

				add_action( 'bp_search_settings_item_members', array( $instance, 'print_search_options' ) );
			}

			// Always return the instance
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		private function __construct() {
			/* Do nothing here */
		}

		/**
		 * Generates sql for members search.
		 *
		 * @todo  : if Mr.X has set privacy of xprofile field 'location' data as 'private'
		 * then, location of Mr.X shouldn't be checked in searched.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string  $search_term
		 * @param boolean $only_totalrow_count
		 *
		 * @return string sql query
		 */
		public function sql( $search_term, $only_totalrow_count = false ) {
			static $selected_xprofile_fields_cache = array();
			global $wpdb, $bp;

			$query_placeholder = array();

			$COLUMNS = ' SELECT ';

			if ( $only_totalrow_count ) {
				$COLUMNS .= ' COUNT( DISTINCT u.id ) ';
			} else {
				$COLUMNS             .= " DISTINCT u.id, 'members' as type, u.display_name LIKE %s AS relevance, a.date_recorded as entry_date ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$FROM = " {$wpdb->users} u LEFT JOIN {$bp->members->table_name_last_activity} a ON a.user_id=u.id AND a.component = 'members' AND a.type = 'last_activity'";

			/**
			 * Filter the MySQL JOIN clause for the Member Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 * @param string $uid_name User ID field name.
			 */
			$FROM = apply_filters( 'bp_user_search_join_sql', $FROM, 'id' );

			$WHERE        = array();
			$WHERE[]      = '1=1';
			$WHERE[]      = 'u.user_status = 0';
			$WHERE[]      = 'a.date_recorded IS NOT NULL';
			$where_fields = array();

			/*
			 ++++++++++++++++++++++++++++++++
			 * wp_users table fields
			 +++++++++++++++++++++++++++++++ */
			$user_fields = bp_get_search_user_fields();
			if ( ! empty( $user_fields ) ) {
				$conditions_wp_user_table = array();
				foreach ( $user_fields as $user_field => $field_label ) {

					if ( ! bp_is_search_user_field_enable( $user_field ) ) {
						continue;
					}

					if ( 'user_meta' === $user_field ) {
						// Search in user meta table for terms
						$conditions_wp_user_table[] = " ID IN ( SELECT user_id FROM {$wpdb->usermeta} WHERE ExtractValue(meta_value, '//text()') LIKE %s AND meta_key NOT IN( 'first_name', 'last_name', 'nickname' ) ) ";
						$query_placeholder[]        = '%' . $search_term . '%';
					} else {
						$conditions_wp_user_table[] = $user_field . ' LIKE %s ';
						$query_placeholder[]        = '%' . $search_term . '%';
					}
				}

				if ( ! empty( $conditions_wp_user_table ) ) {

					$clause_wp_user_table = "u.id IN ( SELECT ID FROM {$wpdb->users}  WHERE ( ";
					$clause_wp_user_table .= implode( ' OR ', $conditions_wp_user_table );

					// get all excluded member types.
					$bp_member_type_ids = bp_get_hidden_member_types();
					$member_type_sql    = $this->get_sql_clause_for_member_types( $bp_member_type_ids, 'NOT IN' );

					if ( ! empty( $member_type_sql ) ) {
						$clause_wp_user_table .= ' ) AND ' . $member_type_sql . ' ) ';
					} else {
						$clause_wp_user_table .= ' ) ) ';
					}

					$where_fields[] = $clause_wp_user_table;

				}
			}
			/* _____________________________ */

			/*
			 ++++++++++++++++++++++++++++++++
			 * xprofile fields
			 +++++++++++++++++++++++++++++++ */
			// get all selected xprofile fields
			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
				$groups = bp_xprofile_get_groups(
					array(
						'fetch_fields'                   => true,
						'repeater_show_main_fields_only' => true,
					)
				);

				if ( ! empty( $groups ) ) {
					$selected_xprofile_fields = array(
						'word_search' => array( 0 ), // Search for whole word in field of type checkbox and radio
						'char_search' => array( 0 ), // Search for character in field of type textbox, textarea and etc
						'date_search' => array( 0 ), // Search for date fields using smart date search.
					);

					$selected_xprofile_repeater_fields = array();

					$word_search_field_type = array( 'radio', 'checkbox' );
					$date_search_field_type = array( 'datebox' );

					foreach ( $groups as $group ) {
						if ( ! empty( $group->fields ) ) {
							foreach ( $group->fields as $field ) {
								if ( bp_is_search_xprofile_enable( $field->id ) ) {

									if ( true === bp_core_hide_display_name_field( $field->id ) ) {
										continue;
									}

									$repeater_enabled = bp_xprofile_get_meta( $field->group_id, 'group', 'is_repeater_enabled', true );

									if ( ! empty( $repeater_enabled ) && 'on' === $repeater_enabled ) {
										$selected_xprofile_repeater_fields = array_unique( array_merge(
											$selected_xprofile_repeater_fields,
											bp_get_repeater_clone_field_ids_all( $field->group_id )
										) );
									} else {
										if ( in_array( $field->type, $word_search_field_type ) ) {
											$selected_xprofile_fields['word_search'][] = $field->id;
										} elseif ( in_array( $field->type, $date_search_field_type ) ) {
											$selected_xprofile_fields['date_search'][] = $field->id;
										} else {
											$selected_xprofile_fields['char_search'][] = $field->id;
										}
									}
								}
							}
						}
					}

					// added repeater support based on privacy.
					if ( ! empty( $selected_xprofile_repeater_fields ) ) {
						$selected_xprofile_repeater_fields = array_unique( $selected_xprofile_repeater_fields );
						foreach ( $selected_xprofile_repeater_fields as $field_id ) {
							$field_object = new BP_XProfile_Field( $field_id );
							if ( in_array( $field_object->type, $word_search_field_type ) ) {
								$selected_xprofile_fields['word_search'][] = $field_object->id;
							} elseif ( in_array( $field_object->type, $date_search_field_type ) ) {
								$selected_xprofile_fields['date_search'][] = $field_object->id;
							} else {
								$selected_xprofile_fields['char_search'][] = $field_object->id;
							}
						}
					}

					if ( ! empty( $selected_xprofile_fields ) ) {

						$cache_key = maybe_serialize( $selected_xprofile_fields['char_search'] );
						$cache_key .= $search_term;
						$cache_key .= maybe_serialize( $selected_xprofile_fields['word_search'] );
						$cache_key .= maybe_serialize( $selected_xprofile_fields['date_search'] );
						$cache_key = md5( $cache_key );
						$user_ids  = array();

						if ( ! isset( $selected_xprofile_fields_cache[ $cache_key ] ) ) {
							// Build the main search query with character and word search.
							$data_clause_xprofile_table = "( SELECT field_id, user_id FROM {$bp->profile->table_name_data} WHERE ( ExtractValue(value, '//text()') LIKE %s AND field_id IN ( ";
							$data_clause_xprofile_table .= implode( ',', $selected_xprofile_fields['char_search'] );
							$data_clause_xprofile_table .= ") ) OR ( value REGEXP '[[:<:]]{$search_term}[[:>:]]' AND field_id IN ( ";
							$data_clause_xprofile_table .= implode( ',', $selected_xprofile_fields['word_search'] );
							$data_clause_xprofile_table .= ') ) ';

							// Add date search if date fields exist and search term is a date.
							if ( ! empty( $selected_xprofile_fields['date_search'] ) && $this->bb_is_date_search( $search_term ) ) {
								$date_field_ids = $selected_xprofile_fields['date_search'];
								$date_values    = $this->bb_parse_date_search( $search_term, $date_field_ids );

								if ( ! empty( $date_field_ids ) && ! empty( $date_values ) ) {
									$date_sql = $this->bb_generate_date_search_sql( $date_values, $date_field_ids );
									if ( ! empty( $date_sql ) ) {
										$data_clause_xprofile_table .= ' OR ( ' . $date_sql . ' ) ';
									}
								}
							}

							$data_clause_xprofile_table .= ' )';

							$sql_xprofile        = $wpdb->prepare( $data_clause_xprofile_table, '%' . $search_term . '%' );
							$sql_xprofile_result = $wpdb->get_results( $sql_xprofile );

							// check visiblity for field id with current user.
							if ( ! empty( $sql_xprofile_result ) ) {
								foreach ( $sql_xprofile_result as $field_data ) {
									$hidden_fields = bp_xprofile_get_hidden_fields_for_user( $field_data->user_id, bp_loggedin_user_id() );

									if (
										( ! empty( $hidden_fields )
										  && ! in_array( $field_data->field_id, $hidden_fields )
										)
										|| empty( $hidden_fields )
									) {
										$user_ids[] = $field_data->user_id;
									}
								}
							}

							$selected_xprofile_fields_cache[ $cache_key ] = array_unique( $user_ids );
						} else {
							$user_ids = $selected_xprofile_fields_cache[ $cache_key ];
						}

						// get all excluded member types.
						$bp_member_type_ids = bp_get_hidden_member_types();
						$member_type_sql    = $this->get_sql_clause_for_member_types( $bp_member_type_ids, 'NOT IN' );

						// Added user when visibility matched.
						if ( ! empty( $user_ids ) ) {
							$user_ids       = array_unique( $user_ids );
							$where_fields[] = "u.id IN ( " . implode( ',', $user_ids ) . " )" . ( $member_type_sql ? ' AND ' . $member_type_sql : '' );
						} else {
							$where_fields[] = "u.id = 0". ( $member_type_sql ? ' AND ' . $member_type_sql : '' );
						}
					}
				}
			}
			/* _____________________________ */

			/*
			 ++++++++++++++++++++++++++++++++
			 * Search from search string
			 +++++++++++++++++++++++++++++++ */

			$split_search_term = explode( ' ', $search_term );

			if ( count( $split_search_term ) > 1 ) {

				$clause_search_string_table = "u.id IN ( SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'bbgs_search_string' AND (";

				foreach ( $split_search_term as $k => $sterm ) {

					if ( $k == 0 ) {
						$clause_search_string_table .= ' meta_value LIKE %s ';
						$query_placeholder[]        = '%' . $sterm . '%';
					} else {
						$clause_search_string_table .= ' OR meta_value LIKE %s ';
						$query_placeholder[]        = '%' . $sterm . '%';
					}
				}

				$clause_search_string_table .= ') ) ';

				$where_fields[] = $clause_search_string_table;

			}

			/* _____________________________ */

			if ( ! empty( $where_fields ) ) {
				$WHERE[] = '(' . implode( ' OR ', $where_fields ) . ')';
			}

			// other conditions
			// $WHERE[] = " a.component = 'members' ";
			// $WHERE[] = " a.type = 'last_activity' ";

			/**
			 * Filters the MySQL WHERE conditions for the member Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $uid_name         User ID field name.
			 */
			$WHERE = apply_filters( 'bp_user_search_where_sql', $WHERE, 'id' );

			$sql = $COLUMNS . ' FROM ' . $FROM . ' WHERE ' . implode( ' AND ', $WHERE );
			if ( ! $only_totalrow_count ) {
				$sql .= ' GROUP BY u.id ';
			}

			if ( ! empty( $query_placeholder ) ) {
				$sql = $wpdb->prepare( $sql, $query_placeholder );
			}

			return apply_filters(
				'Bp_Search_Members_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		protected function generate_html( $template_type = '' ) {
			$group_ids = array();

			foreach ( $this->search_results['items'] as $item_id => $item ) {
				$group_ids[] = $item_id;
			}

			do_action( 'bp_before_search_members_html' );

			// now we have all the posts
			// lets do a groups loop
			if ( bp_has_members( array(
				'search_terms'        => '',
				'include'             => $group_ids,
				'per_page'            => count( $group_ids ),
				'member_type__not_in' => bp_get_hidden_member_types()
			) ) ) {
				while ( bp_members() ) {
					bp_the_member();

					$result_item = array(
						'id'    => bp_get_member_user_id(),
						'type'  => $this->type,
						'title' => bp_get_member_name(),
						'html'  => bp_search_buffer_template_part( 'loop/member', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_member_user_id() ] = $result_item;
				}
			}

			do_action( 'bp_after_search_members_html' );
		}

		/**
		 * What fields members should be searched on?
		 * Prints options to search through username, email, nicename/displayname.
		 * Prints xprofile fields, if xprofile component is active.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		function print_search_options( $items_to_search ) {
			echo "<div class='wp-user-fields' style='margin: 10px 0 0 30px'>";
			echo "<p class='xprofile-group-name' style='margin: 5px 0'><strong>" . __( 'Account', 'buddyboss' ) . '</strong></p>';

			$fields = array(
				'user_login'   => __( 'Username/Login', 'buddyboss' ),
				'display_name' => __( 'Display Name', 'buddyboss' ),
				'user_email'   => __( 'Email', 'buddyboss' ),
				'user_meta'    => __( 'User Meta', 'buddyboss' ),
			);
			foreach ( $fields as $field => $label ) {
				$item    = 'member_field_' . $field;
				$checked = ! empty( $items_to_search ) && in_array( $item, $items_to_search ) ? ' checked' : '';
				echo "<label><input type='checkbox' value='{$item}' name='bp_search_plugin_options[items-to-search][]' {$checked}>{$label}</label><br>";
			}

			echo '</div><!-- .wp-user-fields -->';

			if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'xprofile' ) ) {
				return;
			}

			$groups = bp_xprofile_get_groups(
				array(
					'fetch_fields' => true,
				)
			);

			if ( ! empty( $groups ) ) {
				echo "<div class='xprofile-fields' style='margin: 0 0 10px 30px'>";
				foreach ( $groups as $group ) {
					echo "<p class='xprofile-group-name' style='margin: 5px 0'><strong>" . $group->name . '</strong></p>';

					if ( ! empty( $group->fields ) ) {
						foreach ( $group->fields as $field ) {
							// lets save these as xprofile_field_{field_id}
							$item    = 'xprofile_field_' . $field->id;
							$checked = ! empty( $items_to_search ) && in_array( $item, $items_to_search ) ? ' checked' : '';
							echo "<label><input type='checkbox' value='{$item}' name='bp_search_plugin_options[items-to-search][]' {$checked}>{$field->name}</label><br>";
						}
					}
				}
				echo '</div><!-- .xprofile-fields -->';
			}
		}

		/**
		 * Get a SQL clause representing member_type include/exclusion.
		 *
		 * @since BuddyPress 1.7.6
		 *
		 * @param string|array $member_types Array or comma-separated list of profile types.
		 * @param string       $operator     'IN' or 'NOT IN'.
		 *
		 * @return string
		 */
		protected function get_sql_clause_for_member_types( $member_types, $operator ) {
			global $wpdb;

			// Sanitize.
			if ( 'NOT IN' !== $operator ) {
				$operator = 'IN';
			}

			// Parse and sanitize types.
			if ( ! is_array( $member_types ) ) {
				$member_types = preg_split( '/[,\s+]/', $member_types );
			}

			$types = array();
			foreach ( $member_types as $mt ) {
				if ( bp_get_member_type_object( $mt ) ) {
					$types[] = $mt;
				}
			}

			$tax_query = new WP_Tax_Query(
				array(
					array(
						'taxonomy' => bp_get_member_type_tax_name(),
						'field'    => 'name',
						'operator' => $operator,
						'terms'    => $types,
					),
				)
			);

			// Switch to the root blog, where profile type taxonomies live.
			$site_id  = bp_get_taxonomy_term_site_id( bp_get_member_type_tax_name() );
			$switched = false;
			if ( $site_id !== get_current_blog_id() ) {
				switch_to_blog( $site_id );
				$switched = true;
			}

			$sql_clauses = $tax_query->get_sql( 'u', 'ID' );

			$clause = '';

			// The no_results clauses are the same between IN and NOT IN.
			if ( false !== strpos( $sql_clauses['where'], '0 = 1' ) ) {
				$clause = '0 = 1';

				// The tax_query clause generated for NOT IN can be used almost as-is. We just trim the leading 'AND'.
			} elseif ( 'NOT IN' === $operator ) {
				$clause = preg_replace( '/^\s*AND\s*/', '', $sql_clauses['where'] );

				// IN clauses must be converted to a subquery.
			} elseif ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $sql_clauses['where'], $matches ) ) {
				$clause = "u.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
			}

			if ( $switched ) {
				restore_current_blog();
			}

			return $clause;
		}

		/**
		 * Check if the search term is a date search.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term The search term to check.
		 *
		 * @return bool True if it's a date search, false otherwise.
		 */
		private function bb_is_date_search( $search_term ) {
			// First, try to translate time elapsed expressions to English.
			$english_search = $this->bb_translate_time_elapsed_to_english( $search_term );

			// Check for time elapsed patterns in English.
			$time_elapsed_patterns = array(
				'/^(\d+)\s+(year|month|day)s?\s+(ago|from now)$/i',
				'/^(ago|from now)\s+(\d+)\s+(year|month|day)s?$/i',
			);

			foreach ( $time_elapsed_patterns as $pattern ) {
				if ( preg_match( $pattern, $english_search ) ) {
					return true;
				}
			}

			// Check for other date patterns.
			$combined_pattern = '/^(?:\d{4}-\d{1,2}-\d{1,2}|' . // YYYY-MM-DD.
								'\d{4}\.\d{1,2}\.\d{1,2}|' . // YYYY.MM.DD format.
								'\d{4}\/\d{1,2}\/\d{1,2}|' . // YYYY/MM/DD format.
								'\d{1,2}[\/\-\.\s]\d{1,2}[\/\-\.\s]\d{4}|' . // MM/DD/YYYY, MM-DD-YYYY, MM.DD.YYYY, MM DD YYYY.
								'\d{4}|' . // Year only.
								'\d{4}-\d{1,2}|' . // YYYY-MM format.
								'\d{4}\/\d{1,2}|' . // YYYY/MM format.
								'\d{4}\.\d{1,2}|' . // YYYY.MM format.
								'\d{1,2}[\/\-]\d{4}|' . // MM/YYYY, MM-YYYY.
								'[a-z]+\s+\d{4}|' . // Month name + year.
								'[a-z]+\s+\d{1,2}\s*,\s*\d{4}|' . // Month name + day + comma + year.
								'[a-z]+|' . // Month name only.
								'[a-z]+\s+\d{1,2}|' . // Month name + day.
								'\d{1,2}[\/\-\.\s]\d{1,2}|' . // MM/DD, MM-DD, MM.DD, MM DD.
								'\d+\s+[a-z]+\s+[a-z\s]+|' . // Time elapsed: "34 years ago".
								'[a-z]+\s+\d+\s+[a-z\s]+)$/i'; // Time elapsed: "vor 34 Jahren".

			return preg_match( $combined_pattern, $search_term );
		}

		/**
		 * Parse date search terms and convert to normalized date values.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term    The search term to parse.
		 * @param array  $date_field_ids Array of date field IDs.
		 *
		 * @return array Array of normalized date values.
		 */
		private function bb_parse_date_search( $search_term, $date_field_ids = array() ) {
			$search_term = trim( strtolower( $search_term ) );
			$date_values = array();

			// Get custom date formats for the fields.
			$custom_formats = $this->bb_get_custom_date_formats( $date_field_ids );

			// First, try to translate time elapsed expressions to English.
			$english_search = $this->bb_translate_time_elapsed_to_english( $search_term );

			// Parse time elapsed patterns in English.
			if ( preg_match( '/^(\d+)\s+(year|month|day)s?\s+(ago|from now)$/i', $english_search, $matches ) ) {
				// Time elapsed format: "34 years ago".
				$amount    = intval( $matches[1] );
				$unit      = $matches[2];
				$direction = $matches[3];

				$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );

				if ( $target_time ) {
					$target_year   = gmdate( 'Y', $target_time );
					$date_values[] = array(
						'type'  => 'range',
						'start' => $target_year . '-01-01 00:00:00',
						'end'   => $target_year . '-12-31 23:59:59',
					);
				}
			} elseif ( preg_match( '/^(ago|from now)\s+(\d+)\s+(year|month|day)s?$/i', $english_search, $matches ) ) {
				// Time elapsed format: "ago 34 years".
				$direction = $matches[1];
				$amount    = intval( $matches[2] );
				$unit      = $matches[3];

				$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );

				if ( $target_time ) {
					$target_year   = gmdate( 'Y', $target_time );
					$date_values[] = array(
						'type'  => 'range',
						'start' => $target_year . '-01-01 00:00:00',
						'end'   => $target_year . '-12-31 23:59:59',
					);
				}
			} else {
				// Handle other date formats (existing logic).
				// Full date formats.
				if ( preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY-MM-DD format.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[1] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[3], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{4})\.(\d{1,2})\.(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY.MM.DD format.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[1] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[3], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY/MM/DD format.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[1] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[3], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $search_term, $matches ) ) {
					// MM/DD/YYYY or DD/MM/YYYY format - try both interpretations.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $search_term, $matches ) ) {
					// MM-DD-YYYY or DD-MM-YYYY format - try both interpretations.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $search_term, $matches ) ) {
					// MM.DD.YYYY or DD.MM.YYYY format - try both interpretations.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{1,2})\s+(\d{1,2})\s+(\d{4})$/', $search_term, $matches ) ) {
					// MM DD YYYY or DD MM YYYY format - try both interpretations.
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $matches[3] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[1], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
					);
				} elseif ( preg_match( '/^(\d{4})$/', $search_term, $matches ) ) {
					// Year only - return range for entire year.
					$year          = $matches[1];
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-01-01 00:00:00',
						'end'   => $year . '-12-31 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{4})\/(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY/MM format.
					$year          = $matches[1];
					$month         = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-' . $month . '-01 00:00:00',
						'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{4})\.(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY.MM format.
					$year          = $matches[1];
					$month         = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-' . $month . '-01 00:00:00',
						'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{1,2})\/(\d{4})$/', $search_term, $matches ) ) {
					// MM/YYYY format.
					$month         = str_pad( $matches[1], 2, '0', STR_PAD_LEFT );
					$year          = $matches[2];
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-' . $month . '-01 00:00:00',
						'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{4})-(\d{1,2})$/', $search_term, $matches ) ) {
					// YYYY-MM format.
					$year          = $matches[1];
					$month         = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-' . $month . '-01 00:00:00',
						'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{1,2})-(\d{4})$/', $search_term, $matches ) ) {
					// MM-YYYY format.
					$month         = str_pad( $matches[1], 2, '0', STR_PAD_LEFT );
					$year          = $matches[2];
					$date_values[] = array(
						'type'  => 'range',
						'start' => $year . '-' . $month . '-01 00:00:00',
						'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
					);
				} elseif ( preg_match( '/^(\d{1,2})\/(\d{1,2})$/', $search_term, $matches ) ) {
					// MM/DD or DD/MM format without year - try both interpretations.
					$first  = intval( $matches[1] );
					$second = intval( $matches[2] );

					// Try MM/DD interpretation (first as month, second as day).
					if ( $first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $first, $second, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}

					// Try DD/MM interpretation (first as day, second as month).
					if ( $second >= 1 && $second <= 12 && $first >= 1 && $first <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $second, $first, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}
				} elseif ( preg_match( '/^(\d{1,2})-(\d{1,2})$/', $search_term, $matches ) ) {
					// MM-DD or DD-MM format without year - try both interpretations.
					$first  = intval( $matches[1] );
					$second = intval( $matches[2] );

					// Try MM-DD interpretation (first as month, second as day).
					if ( $first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $first, $second, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}

					// Try DD-MM interpretation (first as day, second as month).
					if ( $second >= 1 && $second <= 12 && $first >= 1 && $first <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $second, $first, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}
				} elseif ( preg_match( '/^(\d{1,2})\.(\d{1,2})$/', $search_term, $matches ) ) {
					// MM.DD or DD.MM format without year - try both interpretations.
					$first  = intval( $matches[1] );
					$second = intval( $matches[2] );

					// Try MM.DD interpretation (first as month, second as day).
					if ( $first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $first, $second, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}

					// Try DD.MM interpretation (first as day, second as month).
					if ( $second >= 1 && $second <= 12 && $first >= 1 && $first <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $second, $first, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}
				} elseif ( preg_match( '/^(\d{1,2})\s+(\d{1,2})$/', $search_term, $matches ) ) {
					// MM DD or DD MM format without year - try both interpretations.
					$first  = intval( $matches[1] );
					$second = intval( $matches[2] );

					// Try MM DD interpretation (first as month, second as day).
					if ( $first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $first, $second, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}

					// Try DD MM interpretation (first as day, second as month).
					if ( $second >= 1 && $second <= 12 && $first >= 1 && $first <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $second, $first, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $second, 2, '0', STR_PAD_LEFT );
							$day           = str_pad( $first, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day,
							);
						}
					}
				} elseif ( preg_match( '/^([a-z]+)\s+(\d{4})$/i', $search_term, $matches ) ) {
					// Month name + year.
					$month_name = strtolower( $matches[1] );
					$year       = $matches[2];
					$month_num  = $this->bb_get_month_number( $month_name );

					if ( $month_num ) {
						$month         = str_pad( $month_num, 2, '0', STR_PAD_LEFT );
						$date_values[] = array(
							'type'  => 'range',
							'start' => $year . '-' . $month . '-01 00:00:00',
							'end'   => $year . '-' . $month . '-' . gmdate( 't', strtotime( $year . '-' . $month . '-01' ) ) . ' 23:59:59',
						);
					}
				} elseif ( preg_match( '/^([a-z]+)$/i', $search_term, $matches ) ) {
					// Month name only.
					$month_name = strtolower( $matches[1] );
					$month_num  = $this->bb_get_month_number( $month_name );
					if ( $month_num ) {
						$month         = str_pad( $month_num, 2, '0', STR_PAD_LEFT );
						$date_values[] = array(
							'type'    => 'partial',
							'pattern' => $month,
						);
					}
				} elseif ( preg_match( '/^([a-z]+)\s+(\d{1,2})$/i', $search_term, $matches ) ) {
					// Month name + day.
					$month_name = strtolower( $matches[1] );
					$day        = intval( $matches[2] );
					$month_num  = $this->bb_get_month_number( $month_name );
					if ( $month_num && $day >= 1 && $day <= 31 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $month_num, $day, (int) gmdate( 'Y' ) ) ) {
							$month         = str_pad( $month_num, 2, '0', STR_PAD_LEFT );
							$day_padded    = str_pad( $day, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'    => 'partial',
								'pattern' => $month . '-' . $day_padded,
							);
						}
					}
				} elseif ( preg_match( '/^([a-z]+)\s+(\d{1,2})\s*,\s*(\d{4})$/i', $search_term, $matches ) ) {
					// Month name + day + comma + year.
					$month_name = strtolower( $matches[1] );
					$day        = intval( $matches[2] );
					$year       = intval( $matches[3] );
					$month_num  = $this->bb_get_month_number( $month_name );
					if ( $month_num && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100 ) {
						// Validate the date using checkdate with current year.
						if ( checkdate( $month_num, $day, $year ) ) {
							$month         = str_pad( $month_num, 2, '0', STR_PAD_LEFT );
							$day_padded    = str_pad( $day, 2, '0', STR_PAD_LEFT );
							$date_values[] = array(
								'type'  => 'exact',
								'value' => $year . '-' . $month . '-' . $day_padded . ' 00:00:00',
							);
						}
					}
				}
			}

			// Try parsing with custom formats.
			$custom_date_values = $this->bb_parse_custom_date_formats( $search_term, $custom_formats );
			if ( ! empty( $custom_date_values ) ) {
				foreach ( $custom_date_values as $custom_value ) {
					$date_values[] = array(
						'type'  => 'exact',
						'value' => $custom_value,
					);
				}
			}

			return $date_values;
		}

		/**
		 * Get month number from month name.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $month_name Month name or abbreviation.
		 *
		 * @return int|false Month number (1-12) or false if not found.
		 */
		private function bb_get_month_number( $month_name ) {
			// Cache months for performance.
			static $months_cache = null;

			// Initialize cache only once.
			if ( null === $months_cache ) {
				$months_cache = array(
					'january'   => 1,
					'jan'       => 1,
					'february'  => 2,
					'feb'       => 2,
					'march'     => 3,
					'mar'       => 3,
					'april'     => 4,
					'apr'       => 4,
					'may'       => 5,
					'june'      => 6,
					'jun'       => 6,
					'july'      => 7,
					'jul'       => 7,
					'august'    => 8,
					'aug'       => 8,
					'september' => 9,
					'sep'       => 9,
					'october'   => 10,
					'oct'       => 10,
					'november'  => 11,
					'nov'       => 11,
					'december'  => 12,
					'dec'       => 12,
				);
			}

			$month_name = strtolower( trim( $month_name ) );

			return isset( $months_cache[ $month_name ] ) ? $months_cache[ $month_name ] : false;
		}

		/**
		 * Calculate target date based on time elapsed expression.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $amount       The amount of time.
		 * @param string $unit         The time unit (year, month, day).
		 * @param string $direction    The direction ('ago' or 'from now').
		 * @param int    $current_time Current timestamp.
		 *
		 * @return int|false Target timestamp or false on error.
		 */
		private function bb_calculate_time_elapsed_date( $amount, $unit, $direction, $current_time ) {
			$multiplier = ( 'ago' === $direction ) ? -1 : 1;

			// Handle both singular and plural units.
			$unit_singular = rtrim( $unit, 's' ); // Remove 's' to get singular form.

			switch ( $unit_singular ) {
				case 'year':
					$target_time = strtotime( ( $amount * $multiplier ) . ' years', $current_time );
					break;
				case 'month':
					$target_time = strtotime( ( $amount * $multiplier ) . ' months', $current_time );
					break;
				case 'day':
					$target_time = strtotime( ( $amount * $multiplier ) . ' days', $current_time );
					break;
				default:
					return false;
			}

			return $target_time;
		}

		/**
		 * Generate SQL conditions for date search.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $date_values Array of normalized date values.
		 * @param array $field_ids   Array of date field IDs to search in.
		 *
		 * @return string SQL WHERE condition for date search.
		 */
		private function bb_generate_date_search_sql( $date_values, $field_ids ) {
			global $wpdb;

			if ( empty( $date_values ) || empty( $field_ids ) ) {
				return '';
			}

			$field_ids_imploded = implode( ',', $field_ids );
			$conditions         = array();

			foreach ( $date_values as $date_value ) {
				switch ( $date_value['type'] ) {
					case 'exact':
						// Exact date search.
						$conditions[] = $wpdb->prepare(
							"field_id IN ({$field_ids_imploded}) AND DATE(value) = %s",
							$date_value['value']
						);
						break;

					case 'range':
						// Date range search.
						$conditions[] = $wpdb->prepare(
							"field_id IN ({$field_ids_imploded}) AND DATE(value) BETWEEN %s AND %s",
							$date_value['start'],
							$date_value['end']
						);
						break;

					case 'partial':
						// Partial date search (month-only or day/month).
						if ( false !== strpos( $date_value['pattern'], '-' ) ) {
							// Day/month pattern (e.g., "06-18").
							$conditions[] = $wpdb->prepare(
								"field_id IN ({$field_ids_imploded}) AND DATE_FORMAT(DATE(value), '%%m-%%d') = %s",
								$date_value['pattern']
							);
						} else {
							// Month-only pattern (e.g., "06").
							$conditions[] = $wpdb->prepare(
								"field_id IN ({$field_ids_imploded}) AND DATE_FORMAT(DATE(value), '%%m') = %s",
								$date_value['pattern']
							);
						}
						break;
				}
			}

			return '(' . implode( ' OR ', $conditions ) . ')';
		}

		/**
		 * Get custom date formats for date fields.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $field_ids Array of field IDs.
		 *
		 * @return array Array of custom date formats.
		 */
		private function bb_get_custom_date_formats( $field_ids ) {
			$custom_formats = array();

			if ( ! empty( $field_ids ) ) {
				foreach ( $field_ids as $field_id ) {
					// Skip placeholder field ID.
					if ( 0 === $field_id ) {
						continue;
					}

					$settings = BP_XProfile_Field_Type_Datebox::get_field_settings( $field_id );

					if ( isset( $settings['date_format'] ) && 'custom' === $settings['date_format'] && ! empty( $settings['date_format_custom'] ) ) {
						$custom_formats[] = array(
							'field_id' => $field_id,
							'format'   => $settings['date_format_custom'],
						);
					} elseif ( isset( $settings['date_format'] ) && 'elapsed' !== $settings['date_format'] ) {
						$custom_formats[] = array(
							'field_id' => $field_id,
							'format'   => $settings['date_format'],
						);
					}
				}
			}

			return $custom_formats;
		}

		/**
		 * Parse search term using custom date formats.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term    Search term.
		 * @param array  $custom_formats Array of custom formats.
		 *
		 * @return array Array of parsed date values.
		 */
		private function bb_parse_custom_date_formats( $search_term, $custom_formats ) {
			$date_values = array();

			if ( empty( $custom_formats ) ) {
				return $date_values;
			}

			foreach ( $custom_formats as $format_info ) {
				// Try to parse the search term using this custom format.
				$parsed_date = $this->bb_parse_date_with_format( $search_term, $format_info['format'] );

				if ( $parsed_date ) {
					$date_values[] = $parsed_date . ' 00:00:00';
				}
			}

			return $date_values;
		}

		/**
		 * Parse date string using a specific format.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $date_string Date string to parse.
		 * @param string $format      Date format to use.
		 *
		 * @return string|false Parsed date in Y-m-d format or false on failure.
		 */
		private function bb_parse_date_with_format( $date_string, $format ) {
			// Handle common formats directly for reliability.
			switch ( $format ) {
				case 'm/d/Y':
					if ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
						$month = str_pad( $matches[1], 2, '0', STR_PAD_LEFT );
						$day   = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
						$year  = $matches[3];

						if ( checkdate( intval( $month ), intval( $day ), intval( $year ) ) ) {
							return $year . '-' . $month . '-' . $day;
						}
					}
					break;

				case 'd/m/Y':
					if ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_string, $matches ) ) {
						$day   = str_pad( $matches[1], 2, '0', STR_PAD_LEFT );
						$month = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
						$year  = $matches[3];

						if ( checkdate( intval( $month ), intval( $day ), intval( $year ) ) ) {
							return $year . '-' . $month . '-' . $day;
						}
					}
					break;

				case 'Y-m-d':
					if ( preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date_string, $matches ) ) {
						$year  = $matches[1];
						$month = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
						$day   = str_pad( $matches[3], 2, '0', STR_PAD_LEFT );

						if ( checkdate( intval( $month ), intval( $day ), intval( $year ) ) ) {
							return $year . '-' . $month . '-' . $day;
						}
					}
					break;

				case 'F j, Y':
					if ( preg_match( '/^([a-zA-Z]+)\s+(\d{1,2}),\s+(\d{4})$/', $date_string, $matches ) ) {
						$month_name = $matches[1];
						$day        = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
						$year       = $matches[3];

						$month_num = $this->bb_get_month_number( $month_name );
						if ( $month_num ) {
							$month = str_pad( $month_num, 2, '0', STR_PAD_LEFT );

							if ( checkdate( intval( $month ), intval( $day ), intval( $year ) ) ) {
								return $year . '-' . $month . '-' . $day;
							}
						}
					}
					break;

				case 'M j, Y':
					if ( preg_match( '/^([a-zA-Z]{3})\s+(\d{1,2}),\s+(\d{4})$/', $date_string, $matches ) ) {
						$month_name = $matches[1];
						$day        = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
						$year       = $matches[3];

						$month_num = $this->bb_get_month_number( $month_name );
						if ( $month_num ) {
							$month = str_pad( $month_num, 2, '0', STR_PAD_LEFT );

							if ( checkdate( intval( $month ), intval( $day ), intval( $year ) ) ) {
								return $year . '-' . $month . '-' . $day;
							}
						}
					}
					break;
			}

			return false;
		}

		/**
		 * Translate time elapsed expressions to English for regex matching.
		 *
		 * This function takes a search term in any language and translates it to English format
		 * for consistent regex matching.
		 *
		 * Process:
		 * 1. Extract the numeric amount (e.g., 32) from the search term.
		 * 2. Get WordPress translations for time units (year/years, month/months, day/days).
		 * 3. Remove direction words (e.g., ago, since, from now) from translations to get base time units.
		 * 4. Extract the shortest word as the base time unit (e.g., year, month, day).
		 * 5. Try partial matching with the base time unit.
		 * 6. Replace direction words with English equivalents (e.g., ago, since, from now).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term The search term to translate.
		 *
		 * @return string The search term in English.
		 */
		private function bb_translate_time_elapsed_to_english( $search_term ) {
			// Static cache for translations to avoid repeated function calls.
			// This improves performance by caching WordPress translation results.
			static $translation_cache = array();

			// Extract the amount from the search term to get the correct plural form.
			// For example: "since 32 years" → $actual_amount = 32.
			if ( preg_match( '/(\d+)/', $search_term, $matches ) ) {
				$actual_amount = intval( $matches[1] );
			} else {
				$actual_amount = 1; // Default fallback if no number found.
			}

			// Define time units to translate.
			// These are the base English time units we'll look for in translations.
			$time_units = array(
				array( 'singular' => 'year', 'plural' => 'years' ),   // Process year/years.
				array( 'singular' => 'month', 'plural' => 'months' ), // Process month/months.  
				array( 'singular' => 'day', 'plural' => 'days' ),     // Process day/days.
			);

			// Translate all time units with caching.
			// This loop processes each time unit (year, month, day) to find matches in the search term.
			foreach ( $time_units as $unit ) {
				// Create a unique cache key for this time unit and amount.
				// Example: "year_32" for 32 years.
				$cache_key = $unit['singular'] . '_' . $actual_amount;

				// Cache WordPress translations to avoid repeated function calls.
				// This improves performance significantly for repeated searches.
				if ( ! isset( $translation_cache[ $cache_key ] ) ) {
					$translation_cache[ $cache_key ] = array(
						// Get singular form translation (e.g., "one year" for "year").
						'singular' => _n( '%s ' . $unit['singular'], '%s ' . $unit['plural'], 1, 'buddyboss' ),
						// Get plural form translation (e.g., "two years" for "years").
						'plural'   => _n( '%s ' . $unit['singular'], '%s ' . $unit['plural'], 2, 'buddyboss' ),
					);
				}

				// Get the cached translations for this time unit.
				$translations = $translation_cache[ $cache_key ];

				// Process this time unit in the search term.
				// This will try to find and replace the time unit with its English equivalent.
				$search_term = $this->bb_translate_time_unit_cached( $search_term, $unit['singular'], $unit['plural'], $translations, $actual_amount );
			}

			// Define direction words to translate.
			// These are the direction indicators like "ago", "since", "from now".
			$directions = array( 'ago', 'since', 'from now' );

			// Translate all direction words with caching.
			// This loop processes each direction word to find and replace them with English equivalents.
			foreach ( $directions as $direction ) {
				// Create a unique cache key for this direction word.
				// Example: "direction_ago".
				$cache_key = 'direction_' . $direction;

				// Cache WordPress translations for direction words.
				if ( ! isset( $translation_cache[ $cache_key ] ) ) {
					// Get the translation for this direction word (e.g., "ago" for "ago").
					$translation_cache[ $cache_key ] = __( '%s ' . $direction, 'buddyboss' );
				}

				// Process this direction word in the search term.
				// This will try to find and replace the direction word with its English equivalent.
				$search_term = $this->bb_translate_direction_word_cached( $search_term, $direction, $translation_cache[ $cache_key ] );
			}

			// Return the final translated search term in English.
			// Example: "since 32 years" → "32 years ago".
			return $search_term;
		}

		/**
		 * Translate a time unit from another language to English.
		 * i.e., Unit: year, month, day.
		 *
		 * This function processes a single time unit (year, month, day) in the search term
		 * and replaces it with its English equivalent. It uses a sophisticated matching
		 * approach that handles complex language structures.
		 *
		 * Process:
		 * 1. Remove %s placeholder from WordPress translations.
		 * 2. Remove direction words (e.g., ago, since, from now) from translations to get base time units.
		 * 3. Extract the shortest word (e.g., year, month, day, one, two, three, etc.) as the base time unit.
		 * 4. Replace any digits in the base unit with the actual amount.
		 * 5. Try partial matching with the base time unit first.
		 * 6. Fallback to exact matching if partial matching fails.
		 *
		 * Example:
		 * Input: "since 32 years"
		 * WordPress translation: "one year"
		 * Base extraction: "year"
		 * Partial match: "year" found in "since 32 years"
		 * Replacement: "year" → "years"
		 * Result: "32 years ago"
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term      The search term to translate.
		 * @param string $english_singular The English singular form (e.g., "year").
		 * @param string $english_plural   The English plural form (e.g., "years").
		 * @param array  $translations     Cached translations from WordPress.
		 * @param int    $actual_amount    The actual amount from search term (e.g., 32).
		 *
		 * @return string The translated search term with time unit replaced.
		 */
		private function bb_translate_time_unit_cached( $search_term, $english_singular, $english_plural, $translations, $actual_amount ) {
			// Remove %s placeholder from WordPress translations.
			// WordPress' translations often include %s for number formatting.
			$singular_word = trim( str_replace( '%s', '', $translations['singular'] ) );
			// Example: "%s year" → "year".

			$plural_word = trim( str_replace( '%s', '', $translations['plural'] ) );
			// Example: "%s years" → "years".

			// Remove direction words and extract base time units.
			// This step removes words like "ago", "since" from the translation to get just the time unit.
			$singular_base = $this->bb_extract_base_time_unit( $this->bb_remove_direction_words( $singular_word ) );
			// Example: "one year" → "year".

			$plural_base = $this->bb_extract_base_time_unit( $this->bb_remove_direction_words( $plural_word ) );
			// Example: "two years" → "two years".

			// Replace digits in the base units with the actual amount from search term.
			// This handles cases where translations include numbers that need to be updated.
			$singular_base = preg_replace( '/\d+/', $actual_amount, $singular_base );
			// Example: "year 1" → "year 32".

			$plural_base = preg_replace( '/\d+/', $actual_amount, $plural_base );
			// Example: "years 2" → "years 32".

			// Try partial matching first, then fallback to exact matching.
			// This approach handles complex language structures where exact matches might not work.
			if ( stripos( $search_term, $plural_base ) !== false ) {
				// Check if the plural base unit exists in the search term.
				// Example: "two years" in "since 32 years" → FALSE.
				$search_term = str_ireplace( $plural_base, $english_plural, $search_term );
			} elseif ( stripos( $search_term, $singular_base ) !== false ) {
				// Check if the singular base unit exists in the search term.
				// Example: "year" in "since 32 years" → TRUE.
				$search_term = str_ireplace( $singular_base, ( 1 === (int) $actual_amount ? $english_singular : $english_plural ), $search_term );
				// Replace it with singular or plural based on amount: 1 = singular, >1 = plural.
				// Example: "year" → "years" (since $actual_amount = 32).
			} elseif ( stripos( $search_term, $plural_word ) !== false ) {
				// Fallback: Check if the full plural word exists in the search term.
				// This handles cases where the base extraction didn't work.
				$search_term = str_ireplace( $plural_word, $english_plural, $search_term );
			} elseif ( stripos( $search_term, $singular_word ) !== false ) {
				// Fallback: Check if the full singular word exists in the search term.
				// This handles cases where the base extraction didn't work.
				$search_term = str_ireplace( $singular_word, ( 1 === (int) $actual_amount ? $english_singular : $english_plural ), $search_term );
			}

			// Return the search term with the time unit replaced.
			// Example: "since 32 years" → "32 years ago".
			return $search_term;
		}

		/**
		 * Remove direction words from translation using WordPress translations.
		 *
		 * This function removes direction words like "ago", "since", "from now" from
		 * WordPress translations to extract just the time unit part.
		 * It uses WordPress translation functions to get the direction words in the current language.
		 *
		 * Process:
		 * 1. Get WordPress translations for direction words (ago, since, from now).
		 * 2. Remove %s placeholder from each translation.
		 * 3. Remove each direction word from the input translation.
		 * 4. Clean up extra spaces and return the cleaned translation.
		 *
		 * Example:
		 * Input: "one year"
		 * Direction words: "ago", "since", "from now", etc.
		 * Process: Remove direction words from "one year"
		 * Result: "one year" (no direction words found, so unchanged)
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $translation The translation to process.
		 *
		 * @return string The translation without direction words.
		 */
		private function bb_remove_direction_words( $translation ) {
			// Get direction words from WordPress translations.
			// These are the direction indicators that might appear in time unit translations.
			$directions = array( 'ago', 'since', 'from now' );

			// Process each direction word to remove it from the translation.
			foreach ( $directions as $direction ) {
				// Get the WordPress translation for this direction word.
				// Example: __('%s ago', 'buddyboss') → "since".
				$direction_translation = __( '%s ' . $direction, 'buddyboss' );

				// Remove the %s placeholder to get just the direction word.
				// Example: "since %s" → "since".
				$direction_clean = trim( str_replace( '%s', '', $direction_translation ) );

				// Remove this direction word from the input translation.
				// Example: "since one year" → "one year".
				$translation = str_replace( $direction_clean, '', $translation );
			}

			// Clean up extra spaces and return the cleaned translation.
			// This removes any double spaces that might have been created during replacement.
			// Example: "one year" → "one year".
			return trim( preg_replace( '/\s+/', ' ', $translation ) );
		}

		/**
		 * Extract the base time unit by finding the shortest word.
		 *
		 * This function extracts the base time unit from a complex translation by finding
		 * the shortest word, which is typically the core time unit. This handles cases
		 * where WordPress translations include modifiers or additional words.
		 *
		 * Process:
		 * 1. Split the time unit into individual words.
		 * 2. Find the shortest word (excluding empty words).
		 * 3. Return the shortest word as the base time unit.
		 *
		 * Example:
		 * Input: "one year"
		 * Words: ["one", "year"]
		 * Lengths: "one" = 3 chars, "year" = 5 chars
		 * Result: "one" - shortest word
		 *
		 * This approach works because:
		 * - Core time units are usually shorter than modifiers.
		 * - "year" is shorter than "one", "two", "single", etc.
		 * - "month" is shorter than "one month", "two months", etc.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $time_unit The time unit to extract base from.
		 *
		 * @return string The base time unit.
		 */
		private function bb_extract_base_time_unit( $time_unit ) {
			// Split the time unit into individual words.
			// This separates the time unit from any modifiers or additional words.
			$words = preg_split( '/\s+/', $time_unit );
			// Example: "one year" → ["one", "year"].

			// Initialize variables to track the shortest word.
			$shortest_word   = '';
			$shortest_length = PHP_INT_MAX;

			// Find the shortest word in the array.
			// The shortest word is typically the core time unit.
			foreach ( $words as $word ) {
				// Check if this word is shorter than the current shortest and not empty.
				if ( strlen( $word ) < $shortest_length && ! empty( $word ) ) {
					$shortest_word   = $word;
					$shortest_length = strlen( $word );
				}
			}

			// Return the shortest word as the base time unit.
			// Example: "one" from "one year".
			return $shortest_word;
		}

		/**
		 * Translate a direction word from another language to English.
		 *
		 * This function processes direction words like "ago", "since", "from now" in the search term
		 * and replaces them with their English equivalents.
		 * It handles complex multi-word direction phrases that might appear in different languages.
		 *
		 * Process:
		 * 1. Remove %s placeholder from WordPress translation.
		 * 2. Split the direction phrase into individual words.
		 * 3. Check if all direction words are present in the search term.
		 * 4. Remove each direction word from the search term.
		 * 5. Add the English direction word to the end.
		 *
		 * Example:
		 * Input: "since 32 years"
		 * WordPress translation: "since"
		 * Direction words: ["since"]
		 * Check: "since" found in "since 32 years" → TRUE
		 * Remove: "since" from "since 32 years" → "32 years"
		 * Add: "ago" to "32 years" → "32 years ago"
		 * Result: "32 years ago"
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term The search term to translate.
		 * @param string $direction   The direction word to translate.
		 * @param string $translation Cached translation from WordPress.
		 *
		 * @return string The translated search term with direction word replaced.
		 */
		private function bb_translate_direction_word_cached( $search_term, $direction, $translation ) {
			// Remove %s placeholder from WordPress translation.
			// WordPress' translations often include %s for number formatting.
			$direction_clean = trim( str_replace( '%s', '', $translation ) );
			// Example: "since %s" → "since".

			// Split the direction phrase into individual words.
			// This handles complex multi-word direction phrases in different languages.
			$direction_words = preg_split( '/\s+/', $direction_clean );
			// Example: "since" → ["since"], "from before" → ["from", "before"].

			// Check if all direction words are present in the search term.
			// This ensures we only replace it when we have a complete match.
			$all_words_present = true;
			foreach ( $direction_words as $word ) {
				// Check if this direction word exists in the search term.
				if ( stripos( $search_term, $word ) === false ) {
					$all_words_present = false;
					break;
				}
			}

			// If all direction words are present, replace them with the English direction.
			// This handles both single-word and multi-word direction phrases.
			if ( $all_words_present ) {
				// Remove each direction word from the search term.
				foreach ( $direction_words as $word ) {
					$search_term = str_ireplace( $word, '', $search_term );
					// Example: Remove "since" from "since 32 years" → "32 years".
				}

				// Add the English direction word to the end.
				$search_term = trim( $search_term ) . ' ' . $direction;
				// Example: Add "ago" to "32 years" → "32 years ago".
			}

			// Return the search term with direction word replaced.
			// Example: "since 32 years" → "32 years ago".
			return $search_term;
		}
	}

	// End class Bp_Search_Members

endif;
