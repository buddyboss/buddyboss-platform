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
		 * Check if the search term contains date patterns.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term Search term to check.
		 *
		 * @return bool True if search term matches date patterns.
		 */
		private function bb_is_date_search( $search_term ) {
			$search_term = trim( strtolower( $search_term ) );

			// Combined regex pattern for all date formats (more efficient than multiple loops).
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
								'\d+\s+(?:year|month|day)s?\s+(?:ago|from\s+now))$/i';

			return preg_match( $combined_pattern, $search_term );
		}

		/**
		 * Parse date search term and return normalized date values.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $search_term    Search term to parse.
		 * @param array  $date_field_ids Array of date field IDs to consider custom formats.
		 *
		 * @return array Array of normalized date values for database query.
		 */
		private function bb_parse_date_search( $search_term, $date_field_ids = array() ) {
			$search_term = trim( strtolower( $search_term ) );
			$date_values = array();

			// Get custom date formats for the fields.
			$custom_formats = $this->bb_get_custom_date_formats( $date_field_ids );

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
			} elseif ( preg_match( '/^(\d+)\s+(year|month|day)s?\s+(ago|from now)$/i', $search_term, $matches ) ) {
				// Time elapsed format.
				$amount    = intval( $matches[1] );
				$unit      = strtolower( $matches[2] );
				$direction = strtolower( $matches[3] );

				$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );

				if ( $target_time ) {
					if ( 'year' === $unit ) {
						// For years, create a year range instead of exact date.
						$target_year   = gmdate( 'Y', $target_time );
						$date_values[] = array(
							'type'  => 'range',
							'start' => $target_year . '-01-01 00:00:00',
							'end'   => $target_year . '-12-31 23:59:59',
						);
					} else {
						// For months and days, use exact date.
						$date_values[] = array(
							'type'  => 'exact',
							'value' => gmdate( 'Y-m-d 00:00:00', $target_time ),
						);
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
		 * Calculate date from time elapsed expression.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $amount       Amount of time.
		 * @param string $unit         Unit of time (year, month, day).
		 * @param string $direction    Direction (ago, from now).
		 * @param int    $current_time Current timestamp.
		 *
		 * @return int|false Calculated timestamp or false on error
		 */
		private function bb_calculate_time_elapsed_date( $amount, $unit, $direction, $current_time ) {
			$multiplier = ( 'ago' === $direction ) ? - 1 : 1;

			switch ( $unit ) {
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
						if ( strpos( $date_value['pattern'], '-' ) !== false ) {
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
	}

	// End class Bp_Search_Members

endif;
