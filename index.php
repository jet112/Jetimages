<?

	require_once('jetimages.php');

	$img = new jetImages();

	$params = array(
		'source'	=> 'test.jpg',
		'method'	=> 'crop',
		'width'		=> 300,
		'height'	=> 300,
		'align'		=> 'center',
		'valign'	=> 0.2,
		'quality'	=> 90,
		'file'	=> 'result.jpg');

	$img->prepare($params);
?>



<div style="">
<img src="result.jpg">
</div>