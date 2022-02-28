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
					);

					$selected_xprofile_repeater_fields = array();

					$word_search_field_type = array( 'radio', 'checkbox' );

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
							} else {
								$selected_xprofile_fields['char_search'][] = $field_object->id;
							}
						}
					}

					if ( ! empty( $selected_xprofile_fields ) ) {

						$cache_key = maybe_serialize( $selected_xprofile_fields['char_search'] );
						$cache_key .= $search_term;
						$cache_key .= maybe_serialize( $selected_xprofile_fields['word_search'] );
						$cache_key = md5( $cache_key );
						$user_ids = array();
						if ( ! isset( $selected_xprofile_fields_cache[ $cache_key ] ) ) {
							$data_clause_xprofile_table = "( SELECT field_id, user_id FROM {$bp->profile->table_name_data} WHERE ( ExtractValue(value, '//text()') LIKE %s AND field_id IN ( ";
							$data_clause_xprofile_table .= implode( ',', $selected_xprofile_fields['char_search'] );
							$data_clause_xprofile_table .= ") ) OR ( value REGEXP '[[:<:]]{$search_term}[[:>:]]' AND field_id IN ( ";
							$data_clause_xprofile_table .= implode( ',', $selected_xprofile_fields['word_search'] );
							$data_clause_xprofile_table .= ') ) )';
							$sql_xprofile               = $wpdb->prepare( $data_clause_xprofile_table, '%' . $search_term . '%' );
							$sql_xprofile_result        = $wpdb->get_results( $sql_xprofile );

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
	}

	// End class Bp_Search_Members

endif;
