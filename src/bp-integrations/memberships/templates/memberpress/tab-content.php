<?php echo !defined('ABSPATH') ? die("Sorry, you can't access this directly - Security established") : '';
foreach ($lmsCourseSlugs as $lmsCourseSlug) {
	if ($lmsCourseSlug == LD_COURSE_SLUG) { ?>

<div class="product_options_page learndash">
    <div class="product-options-panel">
        <!-- Enroll User -->
        <input id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-is_enabled"
        name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-is_enabled" type="checkbox" value="1" <?php checked($isEnabled)?>/>

        <label for="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-is_enabled">
            <?php echo _e('Enroll user in LearnDash course(s) after purchasing this membership.', 'buddyboss'); ?>
        </label>

        <div id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-courses_wrapper" style="display:<?php echo $isEnabled ? 'block' : 'none'; ?>" >

        <div class="post-body-content">
                <!-- Course Access method -->
                <label for="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-course_access_method" style="float: left;padding: 5px">
                <?php echo _e('Course access:', 'buddyboss'); ?>
                </label>
                <select id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-course_access_method" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-course_access_method">
                    <?php foreach ($accessMethods as $key => $text) {?>
                    <option value="<?php echo $key; ?>" <?php echo $key == $courseAccessMethod ? "selected" : '' ?>><?php echo $text; ?></option>
                <?php }?>
                </select>

                <div id="all-course-helper-text" class="helper-text" style="padding-top : 15px" >
                <?php echo _e('Enrolls the user into all existing LearnDash courses, and to any courses added in future.', 'buddyboss'); ?>
                </div>

                <div id="single-course-helper-text" class="helper-text" style="padding-top : 15px" >
                <?php echo _e('Enrolls the user into a single course, or to a set of courses all at once. Select from the courses below:', 'buddyboss'); ?>
                </div>

                 <div id="groups-helper-text" class="helper-text" style="padding-top : 15px" >
                 <?php echo _e('Enrolls the user into a course(s) of this particular group. Select from the groups below:', 'buddyboss'); ?>
                </div>

              </div>

              <!-- Search Course  -->
              <div id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-search_courses_wrapper"><select id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-courses_attached" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-courses_attached[]"></select>
              </div>


              <!-- LearnDash Groups(for courses)  -->
              <div id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-search_groups_wrapper"><select id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-groups_attached" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-groups_attached[]"></select>
              </div>


            <!-- IF Buddyboss Theme -->
            <?php if ($themeName == "BuddyBoss Theme") {?>
            <div class="post-body-content" style="padding-top: 30px">

                <!-- Allow Purchasing -->
                <input id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-allow_from_pricebox" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-allow_from_pricebox" type="checkbox" value="1" <?php checked($allowFromPricebox)?> />

                <label for="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-allow_from_pricebox">
                <?php echo _e('Allow purchasing this product from the course price box.', 'buddyboss'); ?>
                </label>

                <div id="bpms-allow_purchase_wrapper">

                  <div class="post-body-content">
                    <label for="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_text"><?php echo _e('Button text:', 'buddyboss'); ?>
                    </label>
                    <input type="text" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_text" id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_text" placeholder="Purchase" value="<?php echo $buttonText; ?>"/>
                  </div>

                  <div class="post-body-content">
                    <label for="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_order"><?php echo _e('Button order:', 'buddyboss'); ?>
                    <input type="text" name="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_order" id="bpms-<?php echo $lmsCourseSlug . '-' . $membershipProductType; ?>-purchase_button_order" placeholder="0" size="3" value="<?php echo $buttonOrder; ?>"/>
                    </label>
                  </div>

                </div>
            </div>
            </div>
            <?php }?>
    </div>
</div>
<?php } else { /* NOTE : Implementation for another LMS when required */}}?>
<style type="text/css">
.select2-container {
    width: 95% !important;
}
.select2-container .select2-selection__rendered > *:first-child.select2-search--inline {
    width: 95% !important;
}
.select2-container .select2-selection__rendered > *:first-child.select2-search--inline .select2-search__field {
    width: 95% !important;
}

</style>