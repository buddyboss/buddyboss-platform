<?php
/**
 * Class for searching members.
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
		 * Maximum input length for search term.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @var int
		 */
		private static $max_cache_size = 100;

		/**
		 * Date search patterns registry.
		 *
		 * Contains regex patterns for parsing various date formats.
		 * - standard_formats: Numeric date formats (YYYY-MM-DD, MM/DD/YYYY, etc.)
		 * - month_name_formats: Text-based formats with month names.
		 * - time_elapsed_formats: Relative time expressions (5 years ago, etc.).
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @var array {
		 *     @type array $standard_formats     Numeric date format patterns.
		 *     @type array $month_name_formats   Month name format patterns.
		 *     @type array $time_elapsed_formats Relative time format patterns.
		 * }
		 */
		private $date_patterns = array(
			'standard_formats'     => array(
				'yyyy_mm_dd' => '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', // YYYY-MM-DD.
				'yyyy.mm.dd' => '/^(\d{4})\.(\d{1,2})\.(\d{1,2})$/', // YYYY.MM.DD.
				'yyyy/mm/dd' => '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', // YYYY/MM/DD.
				'mm/dd/yyyy' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', // MM/DD/YYYY.
				'dd/mm/yyyy' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', // DD/MM/YYYY.
				'mm-dd-yyyy' => '/^(\d{1,2})-(\d{1,2})-(\d{4})$/', // MM-DD-YYYY.
				'dd-mm-yyyy' => '/^(\d{1,2})-(\d{1,2})-(\d{4})$/', // DD-MM-YYYY.
				'mm.dd.yyyy' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', // MM.DD.YYYY.
				'dd.mm.yyyy' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', // DD.MM.YYYY.
				'mm dd yyyy' => '/^(\d{1,2})\s+(\d{1,2})\s+(\d{4})$/', // MM DD YYYY.
				'dd mm yyyy' => '/^(\d{1,2})\s+(\d{1,2})\s+(\d{4})$/', // DD MM YYYY.
				'year_only'  => '/^(\d{4})$/', // YYYY.
				'yyyy/mm'    => '/^(\d{4})\/(\d{1,2})$/', // YYYY/MM.
				'yyyy.mm'    => '/^(\d{4})\.(\d{1,2})$/', // YYYY.MM.
				'mm/yyyy'    => '/^(\d{1,2})\/(\d{4})$/', // MM/YYYY.
				'yyyy-mm'    => '/^(\d{4})-(\d{1,2})$/', // YYYY-MM.
				'mm-yyyy'    => '/^(\d{1,2})-(\d{4})$/', // MM-YYYY.
				'mm/dd'      => '/^(\d{1,2})\/(\d{1,2})$/', // MM/DD.
				'dd/mm'      => '/^(\d{1,2})\/(\d{1,2})$/', // DD/MM.
				'mm-dd'      => '/^(\d{1,2})-(\d{1,2})$/', // MM-DD.
				'dd-mm'      => '/^(\d{1,2})-(\d{1,2})$/', // DD-MM.
				'mm.dd'      => '/^(\d{1,2})\.(\d{1,2})$/', // MM.DD.
				'dd.mm'      => '/^(\d{1,2})\.(\d{1,2})$/', // DD.MM.
				'mm dd'      => '/^(\d{1,2})\s+(\d{1,2})$/', // MM DD.
				'dd mm'      => '/^(\d{1,2})\s+(\d{1,2})$/', // DD MM.
			),
			'month_name_formats'   => array(
				'month_year'                   => '/^([a-z]+)\s+(\d{4})$/i', // Month name + year.
				'month_comma_year'             => '/^([a-z]+)\s*,\s*(\d{4})$/i', // Month name + comma + year.
				'month_only'                   => '/^([a-z]+)$/i', // Month name only.
				'month_day'                    => '/^([a-z]+)\s+(\d{1,2})$/i', // Month name + day.
				'month_day_comma_year'         => '/^([a-z]+)\s+(\d{1,2})\s*,\s*(\d{4})$/i', // Month name + day + comma + year.
				'day_month'                    => '/^(\d{1,2})\s*([a-z]+)$/i', // Day + Month name.
				'day_month_year'               => '/^(\d{1,2})\s*([a-z]+)\s*\d{4}$/i', // Day + Month name + year.
				'day_month_comma_year'         => '/^(\d{1,2})\s*([a-z]+)\s*,\s*(\d{4})$/i', // Day + Month name + comma + year.
				'month_day_ordinal_comma_year' => '/^([a-z]+)\s+(\d{1,2})(?:st|nd|rd|th)\s*,\s*(\d{4})$/i', // Month name + day with ordinal + comma + year.
				'month_day_ordinal_year'       => '/^([a-z]+)\s+(\d{1,2})(?:st|nd|rd|th)$/i', // Month name + day with ordinal + year.
			),
			'time_elapsed_formats' => array(
				// Simplified patterns to prevent Regex DoS - broken down into smaller, more efficient patterns.
				// Each pattern is atomic and cannot cause catastrophic backtracking.
				'amount_unit_direction_1' => '/^(\d+)\s+(year|month|week|day|hour|minute)s?\s+(ago|from now)$/i',
				'amount_unit_direction_2' => '/^(a|one|an)\s+(year|month|week|day|hour|minute)s?\s+(ago|from now)$/i',
				'amount_unit_direction_3' => '/^(ago|from now)\s+(\d+)\s+(year|month|week|day|hour|minute)s?$/i',
				'amount_unit_direction_4' => '/^(sometime|some time)\s+(ago|from now)$/i',
				'amount_unit_direction_5' => '/^(year|month|week|day|hour|minute)s?\s+(ago|from now)$/i',
			),
		);

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
								$date_values    = $this->bb_parse_date_search( $search_term );

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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to check.
		 *
		 * @return bool True if it's a date search, false otherwise.
		 */
		private function bb_is_date_search( $search_term ) {

			// Check standard date formats.
			if ( $this->bb_is_standard_date_format( $search_term ) ) {
				return true;
			}

			// Check month name patterns.
			if ( $this->bb_is_month_name_pattern( $search_term ) ) {
				return true;
			}

			// Check time elapsed patterns.
			if ( $this->bb_is_time_elapsed_pattern( $search_term ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the search term matches standard date formats.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to check.
		 *
		 * @return bool True if it matches standard date formats, false otherwise.
		 */
		private function bb_is_standard_date_format( $search_term ) {

			// Prevent ReDoS attacks by limiting input length.
			if ( strlen( $search_term ) > 50 ) {
				return false;
			}

			$dd_mm_y_pattern = '/^(?:\d{4}-\d{1,2}-\d{1,2}|' . // YYYY-MM-DD.
								'\d{4}\.\d{1,2}\.\d{1,2}|' . // YYYY.MM.DD format.
								'\d{4}\/\d{1,2}\/\d{1,2}|' . // YYYY/MM/DD format.
								'\d{1,2}[\/\-\.\s]\d{1,2}[\/\-\.\s]\d{4}|' . // MM/DD/YYYY, MM-DD-YYYY, MM.DD.YYYY, MM DD YYYY.
								'\d{4}|' . // Year only.
								'\d{4}-\d{1,2}|' . // YYYY-MM format.
								'\d{4}\/\d{1,2}|' . // YYYY/MM format.
								'\d{4}\.\d{1,2}|' . // YYYY.MM format.
								'\d{1,2}[\/\-]\d{4}|' . // MM/YYYY, MM-YYYY.
								'\d{1,2}[\/\-\.\s]\d{1,2})$/i';

			return preg_match( $dd_mm_y_pattern, $search_term );
		}

		/**
		 * Check if the search term matches month name patterns.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to check.
		 *
		 * @return bool True if it matches month name patterns, false otherwise.
		 */
		private function bb_is_month_name_pattern( $search_term ) {

			// Month name patterns.
			$english_search_term = $this->bb_convert_date_format_with_month_name_to_english( $search_term );

			// Prevent ReDoS attacks by limiting input length.
			if ( strlen( $english_search_term ) > 100 ) {
				return false;
			}

			$month_name_pattern = '/^([a-z]+\s+\d{4}|' . // Month name + year.
								'[a-z]+\s*,\s*\d{4}|' . // Month name + comma + year.
								'[a-z]+\s+\d{1,2}\s*,\s*\d{4}|' . // Month name + day + comma + year.
								'\d{1,2}\s*[a-z]+|' . // Day + Month name.
								'\d{1,2}\s*[a-z]+\s*\d{4}|' . // Day + Month name + year.
								'\d{1,2}\s*[a-z]+\s*,\s*\d{4}|' . // Day + Month name + comma + year.
								'[a-z]+|' . // Month name only.
								'[a-z]+\s+\d{1,2}|' . // Month name + day.
								'[a-z]+\s+\d{1,2}(st|nd|rd|th)\s*,\s*\d{4}|' . // Month name + day with ordinal + comma + year.
								'[a-z]+\s+\d{1,2}(st|nd|rd|th))$/i'; // Month name + day with ordinal.

			return preg_match( $month_name_pattern, $english_search_term );
		}

		/**
		 * Check if the search term matches time elapsed patterns.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to check.
		 *
		 * @return bool True if it matches time elapsed patterns, false otherwise.
		 */
		private function bb_is_time_elapsed_pattern( $search_term ) {
			// Time elapsed patterns.
			$english_search = $this->bb_translate_time_elapsed_to_english( $search_term );

			// Prevent DoS attacks by limiting input length.
			if ( strlen( $english_search ) > 100 ) {
				return false;
			}

			// Check for time elapsed patterns using simplified, more efficient patterns.
			foreach ( $this->date_patterns['time_elapsed_formats'] as $pattern ) {
				if ( preg_match( $pattern, $english_search ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Parse date search terms and convert to normalized date values.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to parse.
		 *
		 * @return array|false Array of normalized date values or false if no date values are found.
		 */
		private function bb_parse_date_search( $search_term ) {
			$search_term = trim( strtolower( $search_term ) );

			// Handle other date formats (existing logic).
			// Full date formats.
			$standard_date_values = $this->bb_parse_standard_date_formats( $search_term ); // Use the original search term.
			if ( ! empty( $standard_date_values ) ) {
				return $standard_date_values;
			}

			// Convert month names to English for consistent processing.
			$converted_month_name_search_term = $this->bb_convert_date_format_with_month_name_to_english( $search_term );
			// Month name patterns.
			$month_name_values = $this->bb_parse_month_name_patterns( $converted_month_name_search_term );
			if ( ! empty( $month_name_values ) ) {
				return $month_name_values;
			}

			// Parse time elapsed patterns in English using a single combined regex.
			$elapsed_search      = $this->bb_translate_time_elapsed_to_english( $search_term );
			$time_elapsed_values = $this->bb_parse_time_elapsed_patterns( $elapsed_search );
			if ( ! empty( $time_elapsed_values ) ) {
				return $time_elapsed_values;
			}

			return false;
		}

		/**
		 * Parse standard date formats.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to parse.
		 *
		 * @return array Array of normalized date values.
		 */
		private function bb_parse_standard_date_formats( $search_term ) {
			$date_values = array();

			// Use the pattern registry to parse standard date formats.
			foreach ( $this->date_patterns['standard_formats'] as $format_type => $pattern ) {

				if ( ! preg_match( $pattern, $search_term, $matches ) ) {
					continue;
				}

				switch ( $format_type ) {
					case 'yyyy_mm_dd':
					case 'yyyy.mm.dd':
					case 'yyyy/mm/dd':
						$year  = intval( $matches[1] );
						$month = intval( $matches[2] );
						$day   = intval( $matches[3] );
						$date  = $this->bb_validate_and_format_date( $year, $month, $day );
						if ( $date ) {
							$date_values[] = $this->bb_create_date_value( 'exact', array( 'value' => $date ) );
						}
						break;

					case 'mm/dd/yyyy':
					case 'dd/mm/yyyy':
					case 'mm-dd-yyyy':
					case 'mm.dd.yyyy':
					case 'dd.mm.yyyy':
					case 'mm dd yyyy':
					case 'dd mm yyyy':
						$year   = intval( $matches[3] );
						$first  = intval( $matches[1] );
						$second = intval( $matches[2] );

						// Try both interpretations.
						$date1 = $this->bb_validate_and_format_date( $year, $first, $second );
						$date2 = $this->bb_validate_and_format_date( $year, $second, $first );

						if ( $date1 ) {
							$date_values[] = $this->bb_create_date_value( 'exact', array( 'value' => $date1 ) );
						}
						if ( $date2 ) {
							$date_values[] = $this->bb_create_date_value( 'exact', array( 'value' => $date2 ) );
						}
						break;

					case 'yyyy/mm':
					case 'yyyy.mm':
					case 'yyyy-mm':
						$year  = intval( $matches[1] );
						$month = intval( $matches[2] );

						$range         = $this->bb_create_month_range( $year, $month );
						$date_values[] = $this->bb_create_date_value( 'range', $range );
						break;

					case 'mm/yyyy':
					case 'mm-yyyy':
						$month = intval( $matches[1] );
						$year  = intval( $matches[2] );

						$range         = $this->bb_create_month_range( $year, $month );
						$date_values[] = $this->bb_create_date_value( 'range', $range );
						break;

					case 'mm/dd':
					case 'dd/mm':
					case 'mm-dd':
					case 'dd-mm':
					case 'mm.dd':
					case 'dd.mm':
					case 'mm dd':
					case 'dd mm':
						$first  = intval( $matches[1] );
						$second = intval( $matches[2] );

						// Try both interpretations with current year.
						$current_year = (int) wp_date( 'Y' );
						$date1        = $this->bb_validate_and_format_date( $current_year, $first, $second );
						$date2        = $this->bb_validate_and_format_date( $current_year, $second, $first );

						if ( $date1 ) {
							$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => substr( $date1, 5 ) ) );
						}
						if ( $date2 && $date2 !== $date1 ) {
							$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => substr( $date2, 5 ) ) );
						}
						break;

					case 'year_only':
						$year          = intval( $matches[1] );
						$range         = array(
							'start' => $year . '-01-01 00:00:00',
							'end'   => $year . '-12-31 23:59:59',
						);
						$date_values[] = $this->bb_create_date_value( 'range', $range );
						break;

					default:
						$date_values[] = array(
							'type'  => 'exact',
							'value' => $matches[1] . '-' . str_pad( $matches[2], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $matches[3], 2, '0', STR_PAD_LEFT ) . ' 00:00:00',
						);
						break;
				}
			}

			return $date_values;
		}

		/**
		 * Parse month name patterns.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to parse.
		 *
		 * @return array Array of normalized date values.
		 */
		private function bb_parse_month_name_patterns( $search_term ) {

			$date_values = array();

			// Use the pattern registry to parse standard date formats.
			foreach ( $this->date_patterns['month_name_formats'] as $format_type => $pattern ) {
				if ( ! preg_match( $pattern, $search_term, $matches ) ) {
					continue;
				}

				switch ( $format_type ) {
					case 'month_year':
					case 'month_comma_year':
						$month_name = strtolower( $matches[1] );
						$year       = $matches[2];
						$month_num  = $this->bb_get_month_number( $month_name );

						if ( $month_num ) {
							$month         = $this->bb_format_number_with_leading_zero( $month_num );
							$range         = $this->bb_create_month_range( $year, $month );
							$date_values[] = $this->bb_create_date_value( 'range', $range );
						}
						break;

					case 'month_only':
					case 'month_day':
						$month_name = strtolower( $matches[1] );
						$month_num  = $this->bb_get_month_number( $month_name );
						if ( $month_num ) {
							$month = $this->bb_format_number_with_leading_zero( $month_num );
							$day   = ! empty( $matches[2] ) ? intval( $matches[2] ) : 0;
							if ( ! empty( $day ) && $day >= 1 && $day <= 31 ) {
								$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => $month . '-' . $day ) );
							} else {
								$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => $month ) );
							}
						}
						break;

					case 'month_day_comma_year':
					case 'month_day_ordinal_comma_year':
					case 'month_day_ordinal_year':
						$month_name          = strtolower( $matches[1] );
						$day                 = intval( $matches[2] );
						$month_num           = $this->bb_get_month_number( $month_name );
						$day_month_condition = $month_num && $day >= 1 && $day <= 31;

						$year           = ! empty( $matches[3] ) ? intval( $matches[3] ) : 0;
						$year_condition = true;

						if ( $day_month_condition ) {
							$month = $this->bb_format_number_with_leading_zero( $month_num );
							$day   = $this->bb_format_number_with_leading_zero( $day );

							if ( ! empty( $year ) ) {
								$year_range     = $this->bb_get_dynamic_year_range();
								$year_condition = $year >= $year_range['min'] && $year <= $year_range['max'];

								if ( $year_condition ) {
									$date_values[] = $this->bb_create_date_value( 'exact', array( 'value' => $year . '-' . $month . '-' . $day . ' 00:00:00' ) );
								}
							} else {
								$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => $month . '-' . $day ) );
							}
						}

						break;

					case 'day_month':
					case 'day_month_year':
					case 'day_month_comma_year':
						$day                 = intval( $matches[1] );
						$month_name          = strtolower( $matches[2] );
						$month_num           = $this->bb_get_month_number( $month_name );
						$day_month_condition = $month_num && $day >= 1 && $day <= 31;

						$year           = ! empty( $matches[3] ) ? intval( $matches[3] ) : 0;
						$year_condition = true;

						if ( $day_month_condition ) {
							$month = $this->bb_format_number_with_leading_zero( $month_num );
							$day   = $this->bb_format_number_with_leading_zero( $day );

							if ( ! empty( $year ) ) {
								$year_range     = $this->bb_get_dynamic_year_range();
								$year_condition = $year >= $year_range['min'] && $year <= $year_range['max'];

								if ( $year_condition ) {
									$date_values[] = $this->bb_create_date_value( 'exact', array( 'value' => $year . '-' . $month . '-' . $day . ' 00:00:00' ) );
								}
							} else {
								$date_values[] = $this->bb_create_date_value( 'partial', array( 'pattern' => $month . '-' . $day ) );
							}
						}
						break;

					default:
						break;
				}
			}

			return $date_values;
		}

		/**
		 * Validate and format a date.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param int $year  The year.
		 * @param int $month The month.
		 * @param int $day   The day.
		 *
		 * @return string|false The formatted date string or false if invalid.
		 */
		private function bb_validate_and_format_date( $year, $month, $day ) {
			$year  = intval( $year );
			$month = intval( $month );
			$day   = intval( $day );

			if ( checkdate( $month, $day, $year ) ) {
				return $this->bb_format_date_string( $year, $month, $day );
			}

			return false;
		}

		/**
		 * Format date components into a standardized date string.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param int $year  The year.
		 * @param int $month The month.
		 * @param int $day   The day.
		 *
		 * @return string The formatted date string.
		 */
		private function bb_format_date_string( $year, $month, $day ) {
			return $year . '-' . $this->bb_format_number_with_leading_zero( $month ) . '-' . $this->bb_format_number_with_leading_zero( $day );
		}

		/**
		 * Format number with leading zero.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param int $number The number to format.
		 *
		 * @return string The formatted number with leading zero.
		 */
		private function bb_format_number_with_leading_zero( $number ) {
			return str_pad( $number, 2, '0', STR_PAD_LEFT );
		}

		/**
		 * Create a month range for a given year and month.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param int $year  The year.
		 * @param int $month The month.
		 *
		 * @return array The month range.
		 */
		private function bb_create_month_range( $year, $month ) {
			$month_padded = $this->bb_format_number_with_leading_zero( $month );
			$start_date   = $this->bb_format_date_string( $year, $month, 1 );
			$timestamp    = strtotime( $year . '-' . $month_padded . '-01' );

			if ( false === $timestamp ) {
				return array(
					'start' => $start_date,
					'end'   => $start_date,
				);
			}

			$last_day = wp_date( 't', $timestamp );
			$end_date = $this->bb_format_date_string( $year, $month, $last_day );

			return array(
				'start' => $start_date,
				'end'   => $end_date,
			);
		}

		/**
		 * Create a date value array.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $type The type of date value (exact, range, partial).
		 * @param array  $data The date data.
		 *
		 * @return array The date value array.
		 */
		private function bb_create_date_value( $type, $data = array() ) {
			$date_value = array( 'type' => $type );

			switch ( $type ) {
				case 'exact':
					$date_value['value'] = $data['value'] . ' 00:00:00';
					break;
				case 'range':
					$date_value['start'] = $data['start'] . ' 00:00:00';
					$date_value['end']   = $data['end'] . ' 23:59:59';
					break;
				case 'partial':
					$date_value['pattern'] = $data['pattern'];
					break;
			}

			return $date_value;
		}

		/**
		 * Parse time elapsed patterns.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term Search term.
		 *
		 * @return array Array of normalized date values.
		 */
		private function bb_parse_time_elapsed_patterns( $search_term ) {
			$date_values = array();

			// Prevent DoS attacks by limiting input length.
			if ( strlen( $search_term ) > 100 ) {
				return $date_values;
			}

			// Parse time elapsed patterns using simplified, more efficient patterns.
			foreach ( $this->date_patterns['time_elapsed_formats'] as $pattern_name => $pattern ) {
				if ( preg_match( $pattern, $search_term, $matches ) ) {

					switch ( $pattern_name ) {
						case 'amount_unit_direction_1':
							// Pattern: "34 years ago" - amount in group 1, unit in group 2, direction in group 3.
							$amount    = intval( $matches[1] );
							$unit      = $matches[2];
							$direction = $matches[3];

							$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );
							if ( $target_time ) {
								$unit_singular = rtrim( $unit, 's' );
								$date_range    = $this->bb_get_optimal_date_range( $target_time, $unit_singular );
								$date_values[] = $this->bb_create_date_value( 'range', $date_range );
							}
							break;

						case 'amount_unit_direction_2':
							// Pattern: "a year ago" or "one year ago" - amount is 1, unit in group 2, direction in group 3.
							$amount    = 1;
							$unit      = $matches[2];
							$direction = $matches[3];

							$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );
							if ( $target_time ) {
								$unit_singular = rtrim( $unit, 's' );
								$date_range    = $this->bb_get_optimal_date_range( $target_time, $unit_singular );
								$date_values[] = $this->bb_create_date_value( 'range', $date_range );
							}
							break;

						case 'amount_unit_direction_3':
							// Pattern: "ago 34 years" - direction in group 1, amount in group 2, unit in group 3.
							$direction = $matches[1];
							$amount    = intval( $matches[2] );
							$unit      = $matches[3];

							$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );
							if ( $target_time ) {
								$unit_singular = rtrim( $unit, 's' );
								$date_range    = $this->bb_get_optimal_date_range( $target_time, $unit_singular );
								$date_values[] = $this->bb_create_date_value( 'range', $date_range );
							}
							break;

						case 'amount_unit_direction_4':
							// Pattern: "sometime ago" - special case for sometime.
							$year_range    = $this->bb_get_dynamic_year_range();
							$date_range    = array(
								'start' => wp_date( 'Y-m-d H:i:s' ),
								'end'   => $year_range['max'] . '-12-31 23:59:59',
							);
							$date_values[] = $this->bb_create_date_value( 'range', $date_range );
							break;

						case 'amount_unit_direction_5':
							// Pattern: "years ago" - unit in group 1, direction in group 2.
							$unit      = $matches[1];
							$direction = $matches[2];
							$amount    = 1; // Default to 1.

							$target_time = $this->bb_calculate_time_elapsed_date( $amount, $unit, $direction, time() );
							if ( $target_time ) {
								$unit_singular = rtrim( $unit, 's' );
								$date_range    = $this->bb_get_optimal_date_range( $target_time, $unit_singular );
								$date_values[] = $this->bb_create_date_value( 'range', $date_range );
							}
							break;
					}

					// Only process the first matching pattern to avoid duplicates.
					break;
				}
			}

			return $date_values;
		}

		/**
		 * Get month names.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @return array Array of month names.
		 */
		public function bb_get_month_names() {
			// English month names for direct lookup.
			$english_months = array(
				'january'   => 1,
				'february'  => 2,
				'march'     => 3,
				'april'     => 4,
				'may'       => 5,
				'june'      => 6,
				'july'      => 7,
				'august'    => 8,
				'september' => 9,
				'october'   => 10,
				'november'  => 11,
				'december'  => 12,
			);

			return $english_months;
		}

		/**
		 * Convert date format term to English.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term that may contain month names.
		 *
		 * @return string The search term with month names converted to English.
		 */
		private function bb_convert_date_format_with_month_name_to_english( $search_term ) {
			global $wp_locale;

			$english_months = $this->bb_get_month_names();

			// Generate month names for current locale.
			for ( $month_num = 1; $month_num <= 12; $month_num++ ) {
				$timestamp  = mktime( 0, 0, 0, $month_num, 1, 2025 );
				$month_name = date_i18n( 'F', $timestamp );

				// Check for nominative case match.
				if ( false !== strpos( $search_term, $month_name ) ) {
					$english_month_name = array_search( $month_num, $english_months, true );
					$search_term        = str_replace( $month_name, $english_month_name, $search_term );
					break;
				}

				if ( isset( $wp_locale->month_genitive[ zeroise( $month_num, 2 ) ] ) ) {
					$genitive_month_name = $wp_locale->month_genitive[ zeroise( $month_num, 2 ) ];

					if ( false !== strpos( $search_term, $genitive_month_name ) ) {
						$english_month_name = array_search( $month_num, $english_months, true );
						$search_term        = str_replace( $genitive_month_name, $english_month_name, $search_term );
						break;
					}
				}
			}

			return $search_term;
		}

		/**
		 * Get month number from month name (supports multiple languages).
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $input_month_name The month name to convert.
		 *
		 * @return int|false Month number (1-12) or false if not found.
		 */
		private function bb_get_month_number( $input_month_name ) {
			$english_months = $this->bb_get_month_names();

			$input_month_name = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $input_month_name ) ) : strtolower( trim( $input_month_name ) );

			// First, try direct English lookup.
			if ( isset( $english_months[ $input_month_name ] ) ) {
				return $english_months[ $input_month_name ];
			}

			global $wp_locale;

			// Check nominative case month names.
			foreach ( $wp_locale->month as $month_key => $month_name ) {
				$month_name_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $month_name ) : strtolower( $month_name );
				if ( $input_month_name === $month_name_lower ) {
					return intval( $month_key );
				}
			}

			// Check genitive case month names.
			if ( isset( $wp_locale->month_genitive ) ) {
				foreach ( $wp_locale->month_genitive as $month_key => $month_name ) {
					$month_name_lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $month_name ) : strtolower( $month_name );
					if ( $input_month_name === $month_name_lower ) {
						return intval( $month_key );
					}
				}
			}

			return false;
		}

		/**
		 * Generate SQL conditions for date search.
		 *
		 * @since BuddyBoss 2.10.0
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

			// Validate that field_ids are integers using WordPress absint function.
			$field_ids_sanitized = array_map( 'absint', $field_ids );
			if ( empty( $field_ids_sanitized ) ) {
				return '';
			}
			$field_ids_placeholders = implode( ',', array_fill( 0, count( $field_ids_sanitized ), '%d' ) );
			$conditions             = array();

			foreach ( $date_values as $date_value ) {
				switch ( $date_value['type'] ) {
					case 'exact':
						// Exact date search.
						$conditions[] = $wpdb->prepare(
							"field_id IN ({$field_ids_placeholders}) AND DATE(value) = %s",
							array_merge( $field_ids_sanitized, array( $date_value['value'] ) )
						);
						break;

					case 'range':
						// Date range search.
						$conditions[] = $wpdb->prepare(
							"field_id IN ({$field_ids_placeholders}) AND DATE(value) BETWEEN %s AND %s",
							array_merge( $field_ids_sanitized, array( $date_value['start'], $date_value['end'] ) )
						);
						break;

					case 'partial':
						// Partial date search (month-only or day/month).
						if ( false !== strpos( $date_value['pattern'], '-' ) ) {
							// Day/month pattern (e.g., "06-18").
							$conditions[] = $wpdb->prepare(
								"field_id IN ({$field_ids_placeholders}) AND DATE_FORMAT(DATE(value), '%%m-%%d') = %s",
								array_merge( $field_ids_sanitized, array( $date_value['pattern'] ) )
							);
						} else {
							// Month-only pattern (e.g., "06").
							$conditions[] = $wpdb->prepare(
								"field_id IN ({$field_ids_placeholders}) AND DATE_FORMAT(DATE(value), '%%m') = %s",
								array_merge( $field_ids_sanitized, array( $date_value['pattern'] ) )
							);
						}
						break;
				}
			}

			return '(' . implode( ' OR ', $conditions ) . ')';
		}

		/**
		 * Calculate dynamic year range for date validation.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @return array Array with 'min' and 'max' year values.
		 */
		private function bb_get_dynamic_year_range() {
			$current_year = (int) wp_date( 'Y' );

			return array(
				'min' => 1965, // Set the minimum year to 1965 as per date range filter.
				'max' => $current_year + 50,
			);
		}

		/**
		 * Get the optimal date range for time elapsed searches.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param int    $target_time   The calculated target timestamp.
		 * @param string $unit_singular The time unit (year, month, week, day).
		 *
		 * @return array Array with 'start' and 'end' dates.
		 */
		private function bb_get_optimal_date_range( $target_time, $unit_singular ) {
			switch ( $unit_singular ) {
				case 'year':
					// For years, use the entire year for birth dates.
					// This ensures we catch all birth dates in that year regardless of calculation precision.
					$target_year = (int) wp_date( 'Y', $target_time );

					return array(
						'start' => $target_year . '-01-01',
						'end'   => $target_year . '-12-31',
					);
				case 'month':
					// For months, use a 2-month range (±1 month).
					return array(
						'start' => wp_date( 'Y-m-d', strtotime( '-1 month', $target_time ) ),
						'end'   => wp_date( 'Y-m-d', strtotime( '+1 month', $target_time ) ),
					);
				case 'week':
					// For weeks, use a 2-week range (±1 week).
					return array(
						'start' => wp_date( 'Y-m-d', strtotime( '-1 week', $target_time ) ),
						'end'   => wp_date( 'Y-m-d', strtotime( '+1 week', $target_time ) ),
					);
				case 'day':
					// For days, use a 7-day range (±3 days).
					return array(
						'start' => wp_date( 'Y-m-d', strtotime( '-3 days', $target_time ) ),
						'end'   => wp_date( 'Y-m-d', strtotime( '+3 days', $target_time ) ),
					);
				default:
					// Default to 15-day range.
					return array(
						'start' => wp_date( 'Y-m-d', strtotime( '-7 days', $target_time ) ),
						'end'   => wp_date( 'Y-m-d', strtotime( '+7 days', $target_time ) ),
					);
			}
		}

		/**
		 * Calculate target date based on time elapsed expression.
		 *
		 * @since BuddyBoss 2.10.0
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
				case 'week':
					$target_time = strtotime( ( $amount * $multiplier ) . ' weeks', $current_time );
					break;
				case 'day':
					$target_time = strtotime( ( $amount * $multiplier ) . ' days', $current_time );
					break;
				case 'hour':
					$target_time = strtotime( ( $amount * $multiplier ) . ' hours', $current_time );
					break;
				case 'minute':
					$target_time = strtotime( ( $amount * $multiplier ) . ' minutes', $current_time );
					break;
				default:
					return false;
			}

			return $target_time;
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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to translate.
		 *
		 * @return string The search term in English.
		 */
		private function bb_translate_time_elapsed_to_english( $search_term ) {
			// Prevent ReDoS attacks by limiting input length before regex processing.
			if ( strlen( $search_term ) > 100 ) {
				return $search_term;
			}

			$search_term = $this->bb_translate_time_units( $search_term );
			$search_term = $this->bb_translate_direction_words( $search_term );

			return $this->bb_add_missing_articles( $search_term );
		}

		/**
		 * Translate time units in the search term to English.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to process.
		 *
		 * @return string The processed search term with translated time units.
		 */
		private function bb_translate_time_units( $search_term ) {
			// Prevent ReDoS attacks by limiting input length before regex processing.
			if ( strlen( $search_term ) > 100 ) {
				return $search_term;
			}

			// Static cache for translations to avoid repeated function calls.
			static $translation_cache = array();

			// Extract the amount from the search term to get the correct plural form.
			if ( preg_match( '/(\d+)/', $search_term, $matches ) ) {
				$actual_amount = intval( $matches[1] );
			} else {
				$actual_amount = 1; // Default fallback if no number found.
			}

			// Define time units to translate.
			$time_units = array(
				// String formats (more specific) - process these FIRST
				'a year', // Process a year (singular only).
				'sometime', // Process sometime.
				'a week', // Process a week (singular only).
				'a day', // Process a day (singular only).
				'an hour', // Process an hour (singular only).
				'a minute', // Process a minute (singular only).

				// Array formats (more general) - process these AFTER string formats
				array(
					'singular' => 'year',
					'plural'   => 'years',
				), // Process year/years.
				array(
					'singular' => 'month',
					'plural'   => 'months',
				), // Process month/months.
				array(
					'singular' => 'week',
					'plural'   => 'weeks',
				), // Process week/weeks.
				array(
					'singular' => 'day',
					'plural'   => 'days',
				), // Process day/days.
				array(
					'singular' => 'hour',
					'plural'   => 'hours',
				), // Process hour/hours.
				array(
					'singular' => 'minute',
					'plural'   => 'minutes',
				), // Process minute/minutes.
			);

			// Translate all time units with caching.
			foreach ( $time_units as $unit ) {
				// Handle both array items (with singular/plural) and string items (like 'sometime').
				if ( is_array( $unit ) ) {
					// Create a unique cache key for this time unit and amount.
					$cache_key = $unit['singular'] . '_' . $actual_amount;

					// Cache WordPress translations to avoid repeated function calls.
					if ( ! isset( $translation_cache[ $cache_key ] ) ) {
						$translation_cache[ $cache_key ] = array(
							// Get singular form translation (e.g., "one year" for "year").
							'singular' => _n( '%s ' . $unit['singular'], '%s ' . $unit['plural'], 1, 'buddyboss' ),
							// Get plural form translation (e.g., "two years" for "years").
							'plural'   => _n( '%s ' . $unit['singular'], '%s ' . $unit['plural'], 2, 'buddyboss' ),
						);

						// Clean up cache if it exceeds the size limit.
						if ( count( $translation_cache ) > self::$max_cache_size ) {
							$translation_cache = array_slice( $translation_cache, -50, null, true );
						}
					}

					// Get the cached translations for this time unit.
					$translations = $translation_cache[ $cache_key ];

					// Process this time unit in the search term.
					$search_term = $this->bb_translate_elipsed_time_unit( $search_term, $unit['singular'], $unit['plural'], $translations, $actual_amount );
				} else {
					// Handle string items like 'sometime'.
					// IMPORTANT: Only process string formats for singular cases (amount = 1)
					if ( 1 === $actual_amount ) {
						$cache_key = $unit . '_' . $actual_amount;

						if ( ! isset( $translation_cache[ $cache_key ] ) ) {
							$translation_cache[ $cache_key ] = array(
								'singular' => __( $unit, 'buddyboss' ),
								'plural'   => __( $unit, 'buddyboss' ),
							);

							// Clean up cache if it exceeds the size limit.
							if ( count( $translation_cache ) > self::$max_cache_size ) {
								$translation_cache = array_slice( $translation_cache, -50, null, true );
							}
						}

						$translations = $translation_cache[ $cache_key ];

						// Process this time unit in the search term.
						$search_term = $this->bb_translate_elipsed_time_unit( $search_term, $unit, $unit, $translations, $actual_amount );
					}
				}
			}

			return $search_term;
		}

		/**
		 * Translate direction words in the search term to English.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to process.
		 *
		 * @return string The processed search term with translated direction words.
		 */
		private function bb_translate_direction_words( $search_term ) {
			// Static cache for translations to avoid repeated function calls.
			static $translation_cache = array();

			// Define direction words to translate.
			$directions = array( 'ago', 'since', 'from now' );

			// Translate all direction words with caching.
			foreach ( $directions as $direction ) {
				// Create a unique cache key for this direction word.
				$cache_key = 'direction_' . $direction;

				// Cache WordPress translations for direction words.
				if ( ! isset( $translation_cache[ $cache_key ] ) ) {
					// Get the translation for this direction word (e.g., "ago" for "ago").
					$translation_cache[ $cache_key ] = __( '%s ' . $direction, 'buddyboss' );

					// Clean up cache if it exceeds the size limit.
					if ( count( $translation_cache ) > self::$max_cache_size ) {
						$translation_cache = array_slice( $translation_cache, -25, null, true );
					}
				}

				// Process this direction word in the search term.
				$search_term = $this->bb_translate_elipsed_direction_word( $search_term, $direction, $translation_cache[ $cache_key ] );
			}

			return $search_term;
		}

		/**
		 * Add missing articles for single time units.
		 *
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to process.
		 *
		 * @return string The processed search term with added articles where needed.
		 */
		private function bb_add_missing_articles( $search_term ) {
			// Add "a" article for single time units if missing.
			// This handles cases like "year ago" → "a year ago".
			if ( preg_match( '/^([^\s]+)\s+(ago|from now)$/i', $search_term ) ) {
				// Extract the time unit word.
				preg_match( '/^([^\s]+)\s+(ago|from now)$/i', $search_term, $matches );
				$time_unit = $matches[1];
				$direction = $matches[2];

				// Check if this looks like a single time unit (not a number)
				// Exclude special words that don't need "a" article.
				if (
					! is_numeric( $time_unit ) &&
					! preg_match( '/^(a|one|an|two|three|four|five|six|seven|eight|nine|ten)$/i', $time_unit ) &&
					! preg_match( '/^(sometime|some time)$/i', $time_unit )
				) {
					$search_term = 'a ' . $search_term;
				}
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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term      The search term to translate.
		 * @param string $english_singular The English singular form (e.g., "year").
		 * @param string $english_plural   The English plural form (e.g., "years").
		 * @param array  $translations     Cached translations from WordPress.
		 * @param int    $actual_amount    The actual amount from search term (e.g., 32).
		 *
		 * @return string The translated search term with time unit replaced.
		 */
		private function bb_translate_elipsed_time_unit( $search_term, $english_singular, $english_plural, $translations, $actual_amount ) {
			// Remove %s placeholder from WordPress translations.
			// WordPress' translations often include %s for number formatting.
			$singular_word = trim( str_replace( '%s', '', $translations['singular'] ) );
			// Example: "%s year" → "year".

			$plural_word = trim( str_replace( '%s', '', $translations['plural'] ) );
			// Example: "%s years" → "years".

			// Remove direction words and extract base time units.
			// This step removes words like "ago", "since" from the translation to get just the time unit.
			$remove_direction_words = $this->bb_remove_direction_words_for_elipsed( $singular_word );
			$singular_base          = $this->bb_extract_base_time_unit_for_elipsed( $remove_direction_words, $english_singular );
			// Example: "one year" → "year".

			$remove_direction_words = $this->bb_remove_direction_words_for_elipsed( $plural_word );
			$plural_base            = $this->bb_extract_base_time_unit_for_elipsed( $remove_direction_words, $english_singular );
			// Example: "two years" → "two years".

			// Replace digits in the base units with the actual amount from search term.
			// This handles cases where translations include numbers that need to be updated.
			$singular_base = preg_replace( '/\d+/', $actual_amount, $singular_base );
			// Example: "year 1" → "year 32".

			$plural_base = preg_replace( '/\d+/', $actual_amount, $plural_base );
			// Example: "years 2" → "years 32".

			// Try partial matching first, then fallback to exact matching.
			// This approach handles complex language structures where exact matches might not work.
			if ( false !== stripos( $search_term, $plural_base ) ) {
				// Check if the plural base unit exists in the search term.
				// Example: "two years" in "since 32 years" → FALSE.
				$search_term = str_ireplace( $plural_base, $english_plural, $search_term );
			} elseif ( false !== stripos( $search_term, $singular_base ) ) {
				// Check if the singular base unit exists in the search term.
				// Example: "year" in "since 32 years" → TRUE.
				$search_term = str_ireplace( $singular_base, ( 1 === (int) $actual_amount ? $english_singular : $english_plural ), $search_term );
				// Replace it with singular or plural based on amount: 1 = singular, >1 = plural.
				// Example: "year" → "years" (since $actual_amount = 32).
			} elseif ( false !== stripos( $search_term, $plural_word ) ) {
				// Fallback: Check if the full plural word exists in the search term.
				// This handles cases where the base extraction didn't work.
				$search_term = str_ireplace( $plural_word, $english_plural, $search_term );
			} elseif ( false !== stripos( $search_term, $singular_word ) ) {
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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $translation The translation to process.
		 *
		 * @return string The translation without direction words.
		 */
		private function bb_remove_direction_words_for_elipsed( $translation ) {
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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $time_unit        The time unit to extract base from.
		 * @param string $english_singular The English singular form.
		 *
		 * @return string The base time unit.
		 */
		private function bb_extract_base_time_unit_for_elipsed( $time_unit, $english_singular = '' ) {
			// Special handling for phrases that should be treated as complete units.
			if (
				in_array(
					$english_singular,
					array(
						'a month',
						'sometime',
						'a year',
						'a week',
						'a day',
						'an hour',
						'a minute',
					),
					true
				)
			) {
				return $time_unit;
			}

			// Remove direction words first.
			$time_unit = $this->bb_remove_direction_words_for_elipsed( $time_unit );

			// Split the time unit into individual words.
			$words = preg_split( '/\s+/', $time_unit );

			// If there's only one word, return it directly.
			if ( count( $words ) === 1 ) {
				return $words[0];
			}

			// For multiple words, use a simple heuristic:
			// Return the first word that's not a common English number word.
			foreach ( $words as $word ) {
				if ( empty( $word ) ) {
					continue;
				}

				// Skip very short words that are likely articles or modifiers.
				if ( strlen( $word ) <= 2 ) {
					continue;
				}

				// Skip common English number words.
				if ( preg_match( '/^(one|two|three|four|five|six|seven|eight|nine|ten|first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth)$/i', $word ) ) {
					continue;
				}

				// Return the first suitable word found.
				return $word;
			}

			// If no suitable word found, return the first non-empty word.
			foreach ( $words as $word ) {
				if ( ! empty( $word ) ) {
					return $word;
				}
			}

			return '';
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
		 * @since BuddyBoss 2.10.0
		 *
		 * @param string $search_term The search term to translate.
		 * @param string $direction   The direction word to translate.
		 * @param string $translation Cached translation from WordPress.
		 *
		 * @return string The translated search term with direction word replaced.
		 */
		private function bb_translate_elipsed_direction_word( $search_term, $direction, $translation ) {
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
				// Use a safer approach to remove direction words to prevent partial replacements.
				// Split the search term into words and check each word individually.
				$words     = preg_split( '/\s+/', $search_term );
				$new_words = array();

				foreach ( $words as $word ) {
					$word_removed = false;

					// Check for exact matches with direction words.
					foreach ( $direction_words as $direction_word ) {
						if ( strcasecmp( $word, $direction_word ) === 0 ) {
							$word_removed = true;
							break;
						}
					}

					// If the word is not a direction word, keep it.
					if ( ! $word_removed ) {
						$new_words[] = $word;
					}
				}

				// Reconstruct the search term without direction words.
				$search_term = implode( ' ', $new_words );

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
