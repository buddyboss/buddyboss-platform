jQuery(document).ready( function() {
    if ( typeof window.Tagify !== 'undefined' ) {
        var input  = document.querySelector('input[name=bbp_topic_tags_tagify]');

        if ( input != null ) {
            window.bbp_tagify = new window.Tagify(input);

			window.bbp_tagify.on('input', onInput);

            window.bbp_tagify.on('add', function () {
                var bbp_topic_tags = '';
                for( var i = 0 ; i < window.bbp_tagify.value.length; i++ ) {
                    bbp_topic_tags += window.bbp_tagify.value[i].value + ',';
                }
                jQuery('#bbp_topic_tags').val(bbp_topic_tags);
            }).on('remove', function () {
                var bbp_topic_tags = '';
                for( var i = 0 ; i < window.bbp_tagify.value.length; i++ ) {
                    bbp_topic_tags += window.bbp_tagify.value[i].value + ',';
                }
                jQuery('#bbp_topic_tags').val(bbp_topic_tags);
            });

            // "remove all tags" button event listener
            jQuery( 'body' ).on('click', '.js-modal-close', window.bbp_tagify.removeAllTags.bind(window.bbp_tagify));
        }
    }

	function onInput( e ){
		var value = e.detail.value;
		window.bbp_tagify.settings.whitelist.length = 0; // reset the whitelist

		var data = {
			'action': 'search_tags',
			'_wpnonce': Common_Data.nonce,
			'tag' : value
		};

		$.ajax({
			type: 'GET',
			url: Common_Data.ajax_url,
			data: data,
			success: function ( response ) {
				if ( response.success ) {
					//window.bbp_tagify.settings.whitelist = ["A# .NET", "A# (Axiom)", "A-0 System", "A+", "A++", "ABAP", "ABC", "ABC ALGOL", "ABSET", "ABSYS", "ACC", "Accent", "Ace DASL", "ACL2", "Avicsoft", "ACT-III", "Action!", "ActionScript", "Ada", "Adenine", "Agda", "Agilent VEE", "Agora", "AIMMS", "Alef", "ALF", "ALGOL 58", "ALGOL 60", "ALGOL 68", "ALGOL W", "Alice", "Alma-0", "AmbientTalk", "Amiga E", "AMOS", "AMPL", "Apex (Salesforce.com)", "APL", "AppleScript", "Arc", "ARexx", "Argus", "AspectJ", "Assembly language", "ATS", "Ateji PX", "AutoHotkey", "Autocoder", "AutoIt", "AutoLISP / Visual LISP", "Averest", "AWK", "Axum", "Active Server Pages", "ASP.NET", "B", "Babbage", "Bash", "BASIC", "bc", "BCPL", "BeanShell", "Batch (Windows/Dos)", "Bertrand", "BETA", "Bigwig", "Bistro", "BitC", "BLISS", "Blockly", "BlooP", "Blue", "Boo", "Boomerang", "Bourne shell (including bash and ksh)", "BREW", "BPEL", "B", "C--", "C++ – ISO/IEC 14882", "C# – ISO/IEC 23270", "C/AL", "Caché ObjectScript", "C Shell", "Caml", "Cayenne", "CDuce", "Cecil", "Cesil", "Céu", "Ceylon", "CFEngine", "CFML", "Cg", "Ch", "Chapel", "Charity", "Charm", "Chef", "CHILL", "CHIP-8", "chomski", "ChucK", "CICS", "Cilk", "Citrine (programming language)", "CL (IBM)", "Claire", "Clarion", "Clean", "Clipper", "CLIPS", "CLIST", "Clojure", "CLU", "CMS-2", "COBOL – ISO/IEC 1989", "CobolScript – COBOL Scripting language", "Cobra", "CODE", "CoffeeScript", "ColdFusion", "COMAL", "Combined Programming Language (CPL)", "COMIT", "Common Intermediate Language (CIL)", "Common Lisp (also known as CL)", "COMPASS", "Component Pascal", "Constraint Handling Rules (CHR)", "COMTRAN", "Converge", "Cool", "Coq", "Coral 66", "Corn", "CorVision", "COWSEL", "CPL", "CPL", "Cryptol", "csh", "Csound", "CSP", "CUDA", "Curl", "Curry", "Cybil", "Cyclone", "Cython", "Java", "Javascript", "M2001", "M4", "M#", "Machine code", "MAD (Michigan Algorithm Decoder)", "MAD/I", "Magik", "Magma", "make", "Maple", "MAPPER now part of BIS", "MARK-IV now VISION:BUILDER", "Mary", "MASM Microsoft Assembly x86", "MATH-MATIC", "Mathematica", "MATLAB", "Maxima (see also Macsyma)", "Max (Max Msp – Graphical Programming Environment)", "Maya (MEL)", "MDL", "Mercury", "Mesa", "Metafont", "Microcode", "MicroScript", "MIIS", "Milk (programming language)", "MIMIC", "Mirah", "Miranda", "MIVA Script", "ML", "Model 204", "Modelica", "Modula", "Modula-2", "Modula-3", "Mohol", "MOO", "Mortran", "Mouse", "MPD", "Mathcad", "MSIL – deprecated name for CIL", "MSL", "MUMPS", "Mystic Programming L"];
					window.bbp_tagify.settings.whitelist = response.data.tags;
					window.bbp_tagify.dropdown.show.call(window.bbp_tagify, value); // render the suggestions dropdown
				}
			}
		});

	}

    if (typeof BP_Nouveau !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.emoji !== 'undefined' ) {
        var bbp_editor_content_elem = false;
        if ( jQuery( '#bbp_editor_topic_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_topic_content';
        } else if ( jQuery( '#bbp_editor_reply_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_reply_content';
        } else if ( jQuery( '#bbp_editor_forum_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_forum_content';
        } else if ( jQuery( '#bbp_topic_content' ).length ) {
            bbp_editor_content_elem = '#bbp_topic_content';
        } else if ( jQuery( '#bbp_reply_content' ).length ) {
            bbp_editor_content_elem = '#bbp_reply_content';
        } else if ( jQuery( '#bbp_forum_content' ).length ) {
            bbp_editor_content_elem = '#bbp_forum_content';
        }
        if (jQuery(bbp_editor_content_elem).length && typeof jQuery.prototype.emojioneArea !== 'undefined' ) {
            jQuery(bbp_editor_content_elem).emojioneArea({
                standalone: true,
                hideSource: false,
                container: jQuery('#whats-new-toolbar > .post-emoji'),
                autocomplete: false,
                pickerPosition: 'bottom',
                hidePickerOnBlur: true,
                useInternalCDN: false,
                events: {
                    ready: function () {
                        if (typeof window.forums_medium_topic_editor !== 'undefined') {
                            window.forums_medium_topic_editor.setContent(jQuery('#bbp_topic_content').val());
                        }
                        if (typeof window.forums_medium_reply_editor !== 'undefined') {
                            window.forums_medium_reply_editor.setContent(jQuery('#bbp_reply_content').val());
                        }
                        if (typeof window.forums_medium_forum_editor !== 'undefined') {
                            window.forums_medium_forum_editor.setContent(jQuery('#bbp_forum_content').val());
                        }
                    },
                    emojibtn_click: function () {
                        if (typeof window.forums_medium_topic_editor !== 'undefined') {
                            window.forums_medium_topic_editor.checkContentChanged();
                        }
                        if (typeof window.forums_medium_reply_editor !== 'undefined') {
                            window.forums_medium_reply_editor.checkContentChanged();
                        }
                        if (typeof window.forums_medium_forum_editor !== 'undefined') {
                            window.forums_medium_forum_editor.checkContentChanged();
                        }
                        jQuery(bbp_editor_content_elem)[0].emojioneArea.hidePicker();
                    },
                }
            });
        }
    }
});
