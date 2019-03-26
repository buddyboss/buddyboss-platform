/* global bpmsVars */
var ajaxUrl = bpmsVars.ajax_url;
var lmsCourseSlugs = bpmsVars.lms_course_slugs;
var membershipProductSlug = bpmsVars.membership_product_slug;
var pId = bpmsVars.p_id;

jQuery(document).ready(function() {
    // NOTE : There is flexibility to use key/value
    jQuery.each(lmsCourseSlugs, function(key, lmsCourseSlug) {
        defaultBehavior(lmsCourseSlug);

        // 1st Toggler
        jQuery(document).on('change', '#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-is_enabled', function() {
            console.log('changed, checkbox()');
            console.log('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-courses_wrapper');

            if (this.checked) {
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-courses_wrapper').show();
            } else {
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-courses_wrapper').hide();
            }
        });

        // 2nd Toggler
        jQuery(document).on('change', '#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-course_access_method', function() {
            console.log(jQuery(this).val());
            if (jQuery(this).val() == 'SINGLE_COURSES') {
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').show();
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').hide();
                jQuery('.helper-text').hide();
                jQuery('#single-course-helper-text').show();
            } else if (jQuery(this).val() == 'ALL_COURSES') {
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').hide();
                jQuery('#bpms-'+ lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').hide();
                jQuery('.helper-text').hide();
                jQuery('#all-course-helper-text').show();
            } else if (jQuery(this).val() == 'LD_GROUPS') {
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').show();
                jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').hide();
                jQuery('.helper-text').hide();
                jQuery('#' + lmsCourseSlug + '-groups-helper-text').show();
                console.log('INFO : LearnDash Groups UI under construction');
            } else {
                // Some other use case for future
            }
        });
        // 3rd Toggler
        jQuery(document).on('change', '#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-allow_from_pricebox', function() {
            console.log('allow checkbox un/checked');
            if (this.checked) {
                jQuery('#bpms-allow_purchase_wrapper').show();
            } else {
                jQuery('#bpms-allow_purchase_wrapper').hide();
            }
        });

        initializeCourseSelect2(lmsCourseSlug, true);
        initializeGroupSelect2(lmsCourseSlug, true);
    });

});
/**
 * Initialize/Configure ui for courses(learndash).
 * @param  {boolean} loadAllAtOnce : Whether to load all values/options in a single ajax call or input-based
 * @return  {void}
 */
function initializeCourseSelect2(lmsCourseSlug, loadAllAtOnce) {
	var search_course_ui = null;
    if (loadAllAtOnce) {

        search_course_ui = jQuery('#bpms-'+ lmsCourseSlug + '-' + membershipProductSlug + '-courses_attached').select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl + '?action=get_courses&lms_course_slug=' + lmsCourseSlug,
                type: 'GET',
                dataType: 'json',
                processResults: function(data) {
                    console.log(data);
                    return {
                        results: data.data.results
                    };
                }
            },
            language: {
                'noResults': function() {
                    return 'No course found with such name';
                }
            },
            width: 'resolve'
        });
    } else {
        search_course_ui = jQuery('#bpms-'+ lmsCourseSlug + '-' + membershipProductSlug + '-courses_attached').select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: function(params) {
                    // Eg   : admin-ajax.php?action=search_courses&search=TERM&type=public
                    var query = {
                        search: params.term,
                        type: 'public',
                        action: 'search_courses',
                        lms_course_slug : lmsCourseSlug
                    };
                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function(data) {
                    return {
                        results: data.data.results
                    };
                }
            },
            language: {
                'noResults': function() {
                    return 'No course found with such name';
                }
            },
            width: 'resolve'
        });
    }
    setPreSavedCourse(lmsCourseSlug, search_course_ui);
}
/**
 * Initialize/Configure ui for groups(learndash).
 * @param  {boolean} loadAllAtOnce : Whether to load all values/options in a single ajax call or input-based
 * @return  {void}
 */
function initializeGroupSelect2(lmsCourseSlug, loadAllAtOnce) {
    var search_group_ui = null;
    if (loadAllAtOnce) {

        search_group_ui = jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-groups_attached').select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl + '?action=get_groups&lms_course_slug=' + lmsCourseSlug,
                type: 'GET',
                dataType: 'json',
                processResults: function(data) {
                    console.log(data);
                    return {
                        results: data.data.results
                    };
                }
            },
            language: {
                'noResults': function() {
                    return 'No group found with such name';
                }
            },
            width: 'resolve'
        });
    } else {
        search_group_ui = jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-groups_attached').select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: function(params) {
                    // Eg   : admin-ajax.php?action=search_groups&lms_course_slug={lmsCourseSlug}&search=TERM&type=public
                    var query = {
                        search: params.term,
                        type: 'public',
                        action: 'search_groups',
                        lms_course_slug: lmsCourseSlug
                    };
                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function(data) {
                    return {
                        results: data.data.results
                    };
                }
            },
            language: {
                'noResults': function() {
                    return 'No group found with such name';
                }
            },
            width: 'resolve'
        });
    }
    setPreSavedGroup(lmsCourseSlug, search_group_ui);
}
/**
 * Set preselected values for courses(learndash).
 * @param  {htmlElement} select2 element
 * @return  {void}
 */
function setPreSavedCourse(lmsCourseSlug, uiSelector) {
    // Set pre-selected values now
    jQuery.ajax({
        url: ajaxUrl + '?action=pre_saved_courses&lms_course_slug='  + lmsCourseSlug + '&membership_product_slug=' + membershipProductSlug + '&pid=' + pId
    }).then(function(jsonData) {
        // console.log(jsonData);
        // var asArray = JSON.parse(jsonData);
        // console.log(asArray);
        jQuery.each(jsonData.data.results, function(index, jsonObj) {
            console.log(jsonObj);
            // create the option and append to Select2
            var option = new Option(jsonObj.text, jsonObj.id, true, true);
            uiSelector.append(option).trigger('change');
        });
    });
}
/**
 * Set preselected values for groups(learndash).
 * @param  {htmlElement} select2 element
 * @return  {void}
 */
function setPreSavedGroup(lmsCourseSlug, uiSelector) {
    // Set pre-selected values now
    jQuery.ajax({
        url: ajaxUrl + '?action=pre_saved_groups&lms_course_slug='  + lmsCourseSlug + '&membership_product_slug=' + membershipProductSlug + '&pid=' + pId
    }).then(function(jsonData) {
        // var asArray = JSON.parse(jsonData);
        // console.log(asArray);
        jQuery.each(jsonData.data.results, function(index, jsonObj) {
            console.log(jsonObj);
            // create the option and append to Select2
            var option = new Option(jsonObj.text, jsonObj.id, true, true);
            uiSelector.append(option).trigger('change');
        });
    });
}

function defaultBehavior(lmsCourseSlug) {
    console.log('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-course_access_method');
    // Selectbox : Course/Group Access
    if (jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-course_access_method option:selected').val() == 'SINGLE_COURSES') {
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').show();
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').hide();
        jQuery('.helper-text').hide();
        jQuery('#single-course-helper-text').show();
    } else if (jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-course_access_method option:selected').val() == 'ALL_COURSES') {
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').hide();
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').hide();
        jQuery('.helper-text').hide();
        jQuery('#all-course-helper-text').show();
    } else if (jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-course_access_method option:selected').val() == 'LD_GROUPS') {
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_groups_wrapper').show();
        jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-search_courses_wrapper').hide();
        jQuery('.helper-text').hide();
        jQuery('#' + lmsCourseSlug + '-groups-helper-text').show();
    } else {
        // Some
    }
    // Checkbox : Allow Purchasing....
    if (jQuery('#bpms-' + lmsCourseSlug + '-' + membershipProductSlug + '-allow_from_pricebox').prop('checked') == true) {
        jQuery('#bpms-allow_purchase_wrapper').show();
    } else {
        jQuery('#bpms-allow_purchase_wrapper').hide();
    }
}
