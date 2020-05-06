(function($){
	'use strict';
	//console.log(JSON.stringify(ZoomMtg.checkSystemRequirements()));

	$(document).on('click', '.join-meeting-in-browser', function(e){
		e.preventDefault();

		var API_KEY = 'o7ffLnqcSsenXvy_yEnwGw';
		var testTool = window.BpZoomTestTool;
		var API_SECRET = 'Lg1o09XtymzRw4ncvHB7eMmT5tNIuIfZD6F5';
		var meetingId = $(this).data('meeting-id');
		var meetingPwd = $(this).data('meeting-pwd');
		var stmUserName = "Local" + ZoomMtg.getJSSDKVersion()[0] + testTool.detectOS() + "#" + testTool.getBrowserInfo();
		var homeUrl = '';

		ZoomMtg.preLoadWasm();
		ZoomMtg.prepareJssdk();

		$('#zmmtg-root').addClass('active');
		var meetConfig = {
			apiKey: API_KEY,
			apiSecret: API_SECRET,
			meetingNumber: parseInt(meetingId),
			userName: stmUserName,
			passWord: meetingPwd,
			leaveUrl:'https://zoom.us',
			role: $(this).data('is-host') == '1' ? 1 : 0,
		};


		var signature = ZoomMtg.generateSignature({
			meetingNumber: meetConfig.meetingNumber,
			apiKey: meetConfig.apiKey,
			apiSecret: meetConfig.apiSecret,
			role: meetConfig.role,
			success: function(res){
				console.log(res.result);
			}
		});

		ZoomMtg.init({
			leaveUrl: meetConfig.leaveUrl,
			isSupportAV: true,
			success: function () {
				ZoomMtg.join(
					{
						meetingNumber: meetConfig.meetingNumber,
						userName: meetConfig.userName,
						signature: signature,
						apiKey: meetConfig.apiKey,
						passWord: meetConfig.passWord,
						success: function(res){
							console.log('join meeting success');
						},
						error: function(res) {
							console.log(res);
						}
					}
				);
			},
			error: function(res) {
				console.log(res);
			}
		});

	});
})(jQuery);
