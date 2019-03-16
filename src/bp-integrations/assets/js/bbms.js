var ajaxUrl = bbmsVars.ajax_url;
var lmsType = bbmsVars.lms_type;
var membershipType = bbmsVars.membership_type;
var pId = bbmsVars.p_id;
console.log(bbmsVars);
jQuery(document).ready(function() {
    defaultBehavior();
    // 1st Toggler
    jQuery(document).on('change', '#bbms-' + lmsType + '-' + membershipType + '-is_enabled', function() {
        console.log("changed, checkbox()");
        console.log('#bbms-' + lmsType + '-' + membershipType + '-courses_wrapper');

        if (this.checked) {
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-courses_wrapper').show();
        } else {
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-courses_wrapper').hide();
        }
    });
    // 2nd Toggler
    jQuery(document).on('change', '#bbms-' + lmsType + '-' + membershipType + '-course_access_method', function() {;
        console.log(jQuery(this).val());
        if (jQuery(this).val() == "SINGLE_COURSES") {
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').show();
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_groups_wrapper').hide();
            jQuery(".helper-text").hide();
            jQuery("#single-course-helper-text").show();
        } else if (jQuery(this).val() == "ALL_COURSES") {
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').hide();
            jQuery('#bbms-'+ lmsType + '-' + membershipType + '-search_groups_wrapper').hide();
            jQuery(".helper-text").hide();
            jQuery("#all-course-helper-text").show();
        } else if (jQuery(this).val() == "LD_GROUPS") {
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_groups_wrapper').show();
            jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').hide();
            jQuery(".helper-text").hide();
            jQuery('#' + lmsType + '-groups-helper-text').show();
            console.log("INFO : Learndash Groups UI under construction");
        } else {
            // Some other use case for future
        }
    });
    // 3rd Toggler
    jQuery(document).on('change', '#bbms-' + lmsType + '-' + membershipType + '-allow_from_pricebox', function() {
        console.log("allow checkbox un/checked");
        if (this.checked) {
            jQuery("#bbms-allow_purchase_wrapper").show();
        } else {
            jQuery("#bbms-allow_purchase_wrapper").hide();
        }
    });
    initializeCourseSelect2(true);
    initializeGroupSelect2(true);
});
/**
 * Initialize/Configure ui for courses(learndash).
 * @param  {boolean} loadAllAtOnce : Whether to load all values/options in a single ajax call or input-based 
 * @return  {void}
 */
function initializeCourseSelect2(loadAllAtOnce) {
    console.log("initializeCourseSelect2, loadAllAtOnce is : " + loadAllAtOnce);
    if (loadAllAtOnce) {
        console.log("Loading loadAllAtOnce");
        var search_course_ui = jQuery('#bbms-'+ lmsType + '-' + membershipType + '-courses_enrolled').select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl + '?action=get_courses',
                type: 'GET',
                dataType: 'json',
                processResults: function(data, params) {
                    console.log(data);
                    return {
                        results: data.data.results
                    };
                },
            },
            language: {
                "noResults": function() {
                    return "No course found with such name";
                },
            },
            width: "resolve"
        });
    } else {
        var search_course_ui = jQuery('#bbms-'+ lmsType + '-' + membershipType + '-courses_enrolled').select2({
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
                        type: "public",
                        action: "search_courses"
                    }
                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function(data, params) {
                    return {
                        results: data.data.results
                    };
                },
            },
            language: {
                "noResults": function() {
                    return "No course found with such name";
                }
            },
            width: "resolve"
        });
    }
    setCoursePreSelected(search_course_ui);
}
/**
 * Initialize/Configure ui for groups(learndash).
 * @param  {boolean} loadAllAtOnce : Whether to load all values/options in a single ajax call or input-based 
 * @return  {void}
 */
function initializeGroupSelect2(loadAllAtOnce) {
    console.log("initializeGroupSelect2, loadAllAtOnce is : " + loadAllAtOnce);
    if (loadAllAtOnce) {
        console.log("Loading loadAllAtOnce");
        var search_group_ui = jQuery('#bbms-' + lmsType + '-' + membershipType + "-groups_attached").select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl + '?action=get_groups', //@todo : verify get_groups call
                type: 'GET',
                dataType: 'json',
                processResults: function(data, params) {
                    console.log(data);
                    return {
                        results: data.data.results
                    };
                },
            },
            language: {
                "noResults": function() {
                    return "No group found with such name";
                },
            },
            width: "resolve"
        });
    } else {
        var search_group_ui = jQuery('#bbms-' + lmsType + '-' + membershipType + "-groups_attached").select2({
            debug: true,
            multiple: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: function(params) {
                    // Eg   : admin-ajax.php?action=search_groups&search=TERM&type=public
                    var query = {
                        search: params.term,
                        type: "public",
                        action: "search_groups" //@todo : verify search_groups call
                    }
                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function(data, params) {
                    return {
                        results: data.data.results
                    };
                },
            },
            language: {
                "noResults": function() {
                    return "No group found with such name";
                }
            },
            width: "resolve"
        });
    }
    setGroupPreSelected(search_group_ui);
}
/**
 * Set preselected values for courses(learndash).
 * @param  {htmlElement} select2 element
 * @return  {void}
 */
function setCoursePreSelected(uiSelector) {
    // Set pre-selected values now
    jQuery.ajax({
        url: ajaxUrl + "?action=selected_courses&meta_key=_bbms-"  + lmsType + "-" + membershipType + "-courses_enrolled&pid=" + pId,
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
function setGroupPreSelected(uiSelector) {
    // Set pre-selected values now
    jQuery.ajax({
        url: ajaxUrl + "?action=selected_groups&meta_key=_bbms-" + lmsType + "-"  + membershipType + "-groups_attached&pid=" + pId,
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

function defaultBehavior() {
    console.log('#bbms-' + lmsType + '-' + membershipType + '-course_access_method');
    // Selectbox : Course/Group Access 
    if (jQuery('#bbms-' + lmsType + '-' + membershipType + '-course_access_method option:selected').val() == "SINGLE_COURSES") {
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').show();
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_groups_wrapper').hide();
        jQuery(".helper-text").hide();
        jQuery('#single-course-helper-text').show();
    } else if (jQuery('#bbms-' + lmsType + '-' + membershipType + '-course_access_method option:selected').val() == "ALL_COURSES") {
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').hide();
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_groups_wrapper').hide();
        jQuery(".helper-text").hide();
        jQuery("#all-course-helper-text").show();
    } else if (jQuery('#bbms-' + lmsType + '-' + membershipType + '-course_access_method option:selected').val() == "LD_GROUPS") {
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_groups_wrapper').show();
        jQuery('#bbms-' + lmsType + '-' + membershipType + '-search_courses_wrapper').hide();
        jQuery(".helper-text").hide();
        jQuery('#' + lmsType + '-groups-helper-text').show();
    } else {
        // Some 
    }
    // Checkbox : Allow Purchasing....
    if (jQuery('#bbms-' + lmsType + '-' + membershipType + '-allow_from_pricebox').prop('checked') == true) {
        jQuery("#bbms-allow_purchase_wrapper").show();
    } else {
        jQuery("#bbms-allow_purchase_wrapper").hide();
    }
}