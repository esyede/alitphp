<?php
require('fw/alit.php');
$fw=Alit::instance();
$fw->set(array('DEBUG'=>3,'UI'=>'ui/'));
$fw->route('GET /',function() use($fw) {
	$data=array(
		'package'=>$fw->get('PACKAGE'),
		'version'=>$fw->get('VERSION'),
		'tagline'=>$fw->get('TAGLINE')
	);
    $fw->render('index',$data);
})->run();
