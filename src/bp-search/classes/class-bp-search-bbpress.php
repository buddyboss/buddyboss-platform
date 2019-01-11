<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('Bp_Search_bbPress')):

	/**
	 *
	 * BuddyPress Global Search  - search bbPress
	 * **************************************
	 *
	 *
	 */
	abstract class Bp_Search_bbPress extends Bp_Search_Type {
		public $type;

		function sql( $search_term, $only_totalrow_count=false ){
			global $wpdb;
			$query_placeholder = array();

			$sql = " SELECT ";

			if( $only_totalrow_count ){
				$sql .= " COUNT( DISTINCT id ) ";
			} else {
				$sql .= " DISTINCT id , '{$this->type}' as type, post_title LIKE %s AS relevance, post_date as entry_date  ";
				$query_placeholder[] = '%'. $search_term .'%';
			}

			$sql .= " FROM 
						{$wpdb->prefix}posts 
					WHERE 
						1=1 
						AND (
								(
										(post_title LIKE %s)
									OR 	(post_content LIKE %s)
								)
							) 
						AND post_type = '{$this->type}'
						AND post_status = 'publish' 
				";
			$query_placeholder[] = '%'. $search_term .'%';
			$query_placeholder[] = '%'. $search_term .'%';
			$sql = $wpdb->prepare( $sql, $query_placeholder );

            return apply_filters(
                'Bp_Search_Forums_sql',
                $sql,
                array(
                    'search_term'           => $search_term,
                    'only_totalrow_count'   => $only_totalrow_count,
                )
            );
		}

		protected function generate_html( $template_type='' ){
			$post_ids = array();
			foreach( $this->search_results['items'] as $item_id=>$item_html ){
				$post_ids[] = $item_id;
			}

			//now we have all the posts
			//lets do a wp_query and generate html for all posts
			$qry = new WP_Query( array( 'post_type' =>array( 'forum', 'topic', 'reply' ), 'post__in'=>$post_ids ) );
			if( $qry->have_posts() ){
				while( $qry->have_posts() ){
					$qry->the_post();

					/**
					 * The following will try to load loop/forum.php, loop/topic.php loop/reply.php(if reply is included).
					 *
					 */
					$result_item = array(
						'id'	=> get_the_ID(),
						'type'	=> $this->type,
						'title'	=> get_the_title(),
						'html'	=> buddyboss_global_search_buffer_template_part( 'loop/' . $this->type, $template_type, false ),
					);

					$this->search_results['items'][get_the_ID()] = $result_item;
				}
			}
			wp_reset_postdata();
		}

	}

// End class Bp_Search_bbPress

endif;
?>
