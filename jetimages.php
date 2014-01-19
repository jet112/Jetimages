<?
class jetImages{

	private $defaultMethod = 'fit';
	private $defaultWidth = 100;
	private $defaultHeight = 100;
	private $defaultQuality = 70;


	/* 
	 * Create image
	 */ 
	private function create($source, $params = false){

		switch(gettype($source)){

			case 'string':

				return $this->file($source);
			break;

			case 'resource':

				if (get_resource_type($source) == 'dg')
					return $source;
			break;

			default:

				$source = imagecreatetruecolor(
					$params['width'] ? $params['width'] : $this->defaultWidth, 
					$params['height'] ? $params['height'] : $this->defaultWidth);

				imagesavealpha($source, true);
   				imagefill($source, 0, 0, imagecolorallocatealpha($source, 255, 255, 255, 127));

   				return $source;
			break;
		}
	}


	/*
	 * GRetrieve file extention
	 */
	public function extention($file){

		$path_info = pathinfo($file);
		return $path_info['extension'];
	}


	/*
	 * Create image from file
	 */
	private function file($source){
		
		$type = $this->type($source);

		if (!$type)
			return false;

		$type = 'imagecreatefrom'.$type;

		return $type($source);
	}


	/*
	 * Return image or save file
	 */
	private function output($params){

		if (!isset($params['source']))
			return false;

		if (isset($params['file'])){

			if(!isset($params['type']))
				$params['type'] = $this->extention($params['file']);

			switch($params['type']){

				case 'png':

					imagepng($params['source'], $params['file']);
				break;

				case 'gif':

					imagegif($params['source'], $params['file']);
				break;

				default:

					$params['quality'] = isset($params['quality']) && $params['quality'] > 0 ? $params['quality'] : $this->defaultQuality;
					imagejpeg($params['source'], $params['file'], $params['quality']);
				break;
			}
		}
		else{

			return $source;
		}
	}


	/*
	 * Prepare image (fit or crop)
	 */
	public function prepare($params = false){

		if (!$params or !is_array($params))
			return false;

		// Source image
		$source = $this->create($params['source']);
		$sourceSize = array('width' => imagesx($source), 'height' => imagesy($source));
		$sourceRatio = $sourceSize['width'] / $sourceSize['height'];

		// Switch method
		$params['method'] = (isset($params['method'])) ? $params['method'] : $this->defaultMethod;

		// prepare image
		switch($params['method']){

			// Fit image into rectangle
			case 'fit':

				if (!is_numeric($params['width']) && !is_numeric($params['height']))
					throw new Exception('Width and height not defined');

				if (!is_numeric($params['width']))
					$params['width'] = $params['height'] * $sourceRatio;

				elseif(!is_numeric($params['height']))
					$params['height'] = $params['width'] / $sourceRatio;

				$paramRatio = $params['width'] / $params['height'];

				$resultSize = array(
					'width' => $sourceRatio > $paramRatio ? $params['width'] : round($params['height'] * $sourceRatio),
					'height' => $sourceRatio > $paramRatio ? round($params['width'] / $sourceRatio) : $params['height']);

				$result = $this->create(false, array('width' => $resultSize['width'], 'height' => $resultSize['height']));

				imagecopyresampled($result, $source, 0, 0, 0, 0, $resultSize['width'], $resultSize['height'], $sourceSize['width'], $sourceSize['height']);
				imagealphablending($result, true);
				imagesavealpha($result, true);

				imagejpeg($result, 'new.jpg', 70);
			break;

			// Crop image
			case 'crop':

				if (!is_numeric($params['width']) or !is_numeric($params['height']))
					throw new Exception('Width or height not defined');

				$paramRatio = $params['width'] / $params['height'];

				// Horizontal align
				if(is_numeric($params['align']) && $params['align'] >= 0 && $params['align'] <= 1){

					$align = $params['align'];
				}
				elseif ($params['align']){

					switch ($params['align']){

						case 'left':
					 		$align = 0;
						break;

						case 'right':

							$align = 1;
						break;

						default:

					 	 	$align = 0.5;
						break;
					}
				}
				else{

					$align = 0.5;
				}

				// Vertical align
				if (is_numeric($params['valign']) && $params['valign'] >= 0 && $params['valign'] <= 1){

					$valign = $params['valign'];
				}
				elseif ($params['valign']){

					switch ($params['valign']){

						case 'top':
								$valign = 0;
							break;

						case 'bottom':

								$valign = 1;
						break;

						default:

							$valign = 0.5;
						break;
					}
				}
				else{

					$valign = 0.5;
				}

				// Crop size
				$cropSize = array(

					'width' => $sourceRatio > $paramRatio ? round($sourceSize['height'] * $paramRatio) : $sourceSize['width'],
					'height' => $sourceRatio > $paramRatio ? $sourceSize['height'] : round($sourceSize['width'] / $paramRatio));

				$cropSize['x'] = round($align * ($sourceSize['width'] - $cropSize['width']));
				$cropSize['y'] = round($valign * ($sourceSize['height'] - $cropSize['height']));

				// Result image
				$result = $this->create(false, array('width' => $params['width'], 'height' => $params['height']));

				imagecopyresampled($result, $source, 0, 0, $cropSize['x'], $cropSize['y'], $params['width'], $params['height'], $cropSize['width'], $cropSize['height']);
				imagealphablending($result, true);
				imagesavealpha($result, true);
			break;

			default:

				return false;
			break;


		}

		// Save file 
		$outputParams = array(
			'file'		=> $params['file'],
			'source'	=> $result,
			'type'		=> $params['type'],
			'quality'	=> $params['quality']
		);

		$this->output($outputParams);
	}


	/*
	 * Translate hex to text
	 */
	private function strhex($string){ 

		$hex = ""; 

		for($i = 0; $i < strlen($string); ++$i) { 

			$hex .= str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT); 
		} 
	
		return $hex; 
	} 


	/*
	 * Detect image type (supports jpeg, png, gif)
	 */
	public function type($filename){

			$file = fopen($filename, 'r');

			if (!$file)
				return false;

		$hdr = $this->strhex(fread($file, 12));

		// Match header of the file 
		if(preg_match('/^ffd8ffe0....4A46494600/i', $hdr)) { 

			return 'jpeg'; 
		} 
		elseif(preg_match('/^89504e470D0a1a0a/i', $hdr)) { 

			return 'png';
		} 
		elseif(preg_match('/^474946/i', $hdr)) { 

			return 'gif'; 
		} 
		else { 

			return false;
		} 
	}
}
?>
