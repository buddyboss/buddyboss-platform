<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages_subjects = array(
	'Aliquam quis lectus',
	'Proin eros',
	'Vivamus sagittis tellus luctus felis',
	'Pellentesque egestas',
	'Aliquam erat volutpat',
	'Donec gravida',
	'Curabitur nec tellus. In semper',
	'Proin tempus porta dui',
	'Nam gravida tempus nibh',
	'Duis ultricess',
);

$messages_content = array(
	'Phasellus facilisis, massa sed egestas condimentum, felis neque condimentum leo, ut ornare libero dolor non enim. Etiam auctor lacus gravida lacus. ',
	'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras vitae ante sit amet massa facilisis facilisis.',
	'Nulla a nulla ac leo interdum mollis. Praesent molestie felis in nunc. Morbi mauris. Suspendisse potenti. In consectetur quam sit amet metus. Cras et dui a felis placerat auctor. Donec bibendum turpis nec dui. Aliquam dolor dui, suscipit ac, placerat volutpat, bibendum et, arcu. Nulla ultricies rhoncus tellus. Mauris et neque sit amet turpis faucibus fringilla. Maecenas ac leo. Nullam ac quam. Etiam a nisi. Mauris rutrum tincidunt pede. Donec nisl nulla, tempus et, molestie id, adipiscing tristique, quam. ',
	'Etiam scelerisque, dui eu viverra malesuada, dolor enim sagittis purus, ac varius libero magna ac purus. Sed porta fringilla mauris. Phasellus tempus condimentum massa. Ut congue tortor ac nunc. Morbi est. Vestibulum auctor metus non ipsum. Donec gravida. Duis ultrices. Suspendisse purus lectus, fringilla at, eleifend vitae, vehicula in, tellus. Fusce cursus elementum ligula. ',
	'Nulla a nulla ac leo interdum mollis. Nulla a nulla ac leo interdum mollis',
	'Quisque sagittis neque. Vestibulum laoreet. Nullam cursus, odio in fringilla lacinia, urna nisi feugiat arcu, nec aliquet leo mi a justo. Sed ipsum massa, elementum nec, hendrerit ut, malesuada sit amet, ante. Nulla nunc odio, viverra at, tincidunt vel, mattis quis, odio.',
	'Aliquam et tellus. Nullam sed nisl. Nullam lobortis dui at odio. Nulla facilisi. Praesent elementum eleifend lectus. Phasellus cursus, diam non consectetur tempus, orci eros tristique sem, at pretium diam enim sed lectus. Mauris in tellus sed dolor pulvinar iaculis. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nunc nec augue. ',
	'Duis ultrices pretium augue. Donec iaculis erat et dui. Pellentesque vel odio. Etiam bibendum, enim ut auctor molestie, metus ante feugiat tortor, at molestie enim nisi id ligula. Aenean in lectus. Sed in turpis. Mauris suscipit dui sed urna. Sed gravida, tellus id suscipit consectetur, ipsum enim tempus lectus, vitae facilisis libero pede id sapien. ',
	' ',
	'Ut consequat. Curabitur molestie, erat eget aliquam porttitor, orci sem commodo risus, eget rhoncus orci purus et massa. Praesent facilisis mi nec nisl semper eleifend. Donec vel magna id nunc adipiscing laoreet. In hac habitasse platea dictumst. Fusce quis odio. Duis vehicula est sit amet tellus. Proin hendrerit. Suspendisse cursus, risus eget malesuada rutrum, risus quam lobortis libero, eget posuere metus urna non nisl. ',
	'Nunc posuere, sem a tempor tristique, velit augue congue tortor, non pellentesque velit eros nec mauris. In sapien nunc, bibendum quis, commodo in, feugiat ac, nulla. Donec nec velit eu sapien ultricies porttitor. Quisque pulvinar, eros vel consectetur facilisis, nisi ante egestas libero, sed euismod turpis libero sed turpis. Aenean a libero.',
	'Vestibulum vulputate nunc faucibus mauris. Nulla vel tortor. Donec quis turpis. Fusce gravida. Maecenas mollis facilisis urna. Morbi feugiat, velit a porta sodales, massa lacus ultricies velit, et pretium nunc ante vitae leo. Pellentesque dolor nibh, sagittis a, commodo vitae, luctus nec, purus. Vivamus vestibulum pede sit amet ligula. Fusce ut nisi. Morbi fringilla. Aenean sapien. Vestibulum eros. Integer auctor lacinia lorem.',
	'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse potenti. Sed mattis diam nec neque. In congue ipsum sit amet turpis. Suspendisse tempus lorem a turpis. ',
	'Nullam interdum eros et ipsum. Phasellus volutpat lacus dapibus enim. Nulla tempor rutrum purus. Sed ornare orci vitae diam. Sed viverra diam. Pellentesque nisi nulla, lacinia sit amet, blandit in, vestibulum sit amet, mi. ',
	'Donec condimentum lacus vitae tortor. Duis ultrices, tellus in pellentesque ullamcorper, neque enim vestibulum dui, sit amet dapibus tortor nisi vel magna. Proin purus nunc, placerat at, adipiscing at, ullamcorper id, leo. Sed consequat risus id odio. Nulla ac sem quis nunc congue tincidunt. ',
	'Etiam erat neque, suscipit at, facilisis vitae, congue quis, dui. Aliquam laoreet quam non velit.',
	'Mauris nunc tellus, bibendum id, feugiat ut, ullamcorper facilisis, sem. Proin lectus. Nulla volutpat consequat metus. Cras ac lorem ac lacus convallis vestibulum. Maecenas pretium. ',
	'Nullam et enim at orci sagittis gravida. Nullam tincidunt, tortor quis pretium laoreet, eros neque dictum felis, nec gravida neque dui sed ligula. Integer fermentum tortor eu elit. In eros. Duis ac augue quis tortor semper cursus. Nulla dignissim blandit nisl. Aliquam sit amet erat. ',
	'Aliquam mattis leo quis augue. In viverra tincidunt velit. Mauris magna. Donec non pede sit amet lacus viverra eleifend. Duis risus purus, posuere rutrum, facilisis at, imperdiet in, eros. Phasellus accumsan ipsum in arcu. Phasellus ultricies velit ac justo.',
	'Praesent sem. In et dui. Ut sodales posuere orci. Ut quam ipsum, semper vel, accumsan sed, porta non, risus. Sed ac magna sit amet libero placerat euismod. ',
	'Sed vitae augue feugiat risus varius tristique. In id nunc at nisi tristique ullamcorper. Sed at leo. Fusce vel mauris tristique diam ultricies posuere.',
	'Fusce dictum luctus nisl. Curabitur vel enim eget massa hendrerit adipiscing. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.',
	'Curabitur risus. In mollis sapien sit amet mi. Duis libero enim, eleifend quis, bibendum quis, aliquet id, massa. Vestibulum iaculis justo non quam. Etiam porttitor pellentesque erat. Pellentesque cursus dignissim arcu. Duis placerat magna vitae nibh.',
	'Nulla convallis. Quisque eget tortor. In turpis tellus, mattis id, pellentesque eu, tincidunt a, nulla. Vivamus sagittis pede sed mauris. Nulla elementum enim a lorem. ',
	'Sed lacus. Donec condimentum lacus vitae tortor. Duis ultrices, tellus in pellentesque ullamcorper, neque enim vestibulum dui, sit amet dapibus tortor nisi vel magna. Proin purus nunc, placerat at, adipiscing at, ullamcorper id, leo. Sed consequat risus id odio. Nulla ac sem quis nunc congue tincidunt. ',
	'Vivamus tempor. Proin pretium ante vel dolor. Sed mauris lorem, lobortis quis, vulputate sit amet, iaculis in, enim. Phasellus quis risus. Donec sagittis. Sed eget risus. Praesent egestas nunc non tellus. Sed nisi mi, tincidunt quis, pretium eget, tincidunt in, est. ',
);
