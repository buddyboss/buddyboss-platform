
            <div class="bp-learndash-group-courses-export-csv">
                <a href="#" class="export-csv"
                   data-menu="<?php echo $this->current_tab; ?>"
                   data-member_id="<?php echo $this->bp_learndash_member_id; ?>"
                   data-courses_id="<?php echo $this->bp_learndash_courses_id; ?>"
                   data-group_id="<?php echo $this->group_id; ?>"
                   data-filename="<?php printf( '%s-export-member-id-%s--courses-id-%s', $this->current_tab, $this->bp_learndash_member_id, $this->bp_learndash_courses_id ) ?>">
					<?php _e( 'Export CSV', 'buddyboss' ); ?>
                    <a id="bp_learndash_group_courses_export_csv_download"></a>
                </a>

				<?php
				printf( "<input type='hidden' name='csv' class='csv' value='%s'>", base64_encode( json_encode( $this->csv ) ) );
				?>
            </div>
