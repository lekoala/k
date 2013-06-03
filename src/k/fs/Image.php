<?php

namespace k\fs;

use \SplFileInfo;
use \InvalidArgumentException;
use \RuntimeException;
use \Exception;

/**
 * Image
 *
 * @author lekoala
 */
class Image extends File {

	public function output() {
		header("Content-Type: " . $this->getMimeType());
		readfile($this->getPathname());
	}
	
	public function resize($width = 0, $height = 0, $proportional = true) {
		return self::imageResize($this->getPathname(), $width, $height, $proportional);
	}
	
	public function crop($width = 0, $height = 0) {
		return self::imageCrop($this->getPathname(), $width, $height);
	}
	
	/**
	 * Create a gd resource from a filename
	 * 
	 * @param string $filename
	 * @return resource
	 */
	public static function imageCreate($filename) {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		switch ($ext) {
			case 'gif':
				$gd = imagecreatefromgif($filename);
				break;
			case 'jpg':
			case 'jpeg':
				$gd = imagecreatefromjpeg($filename);
				break;
			case 'png':
				$gd = imagecreatefrompng($filename);
				break;
			default:
				$gd = imagecreatefromstring(file_get_contents($filename));
		}
		return $gd;
	}

	/**
	 * Get image type from binary (eg through file_get_contents)
	 * @param string $binary
	 * @return string
	 */
	public static function imageTypeFromBinary($binary) {
		if (!preg_match('/\A(?:(GIF8[79]a)|(\xff\xd8\xff)|(\x89PNG\x0d\x0a)))/', $image, $matches)) {
			return 'application/octet-stream';
		}
		//gif = 1, jpeg = 2, png = 3
		return count($matches);
	}

	/**
	 * Save a gd resource to a filename
	 * 
	 * @param resource $image
	 * @param string $filename filename or mimetype for browser output
	 * @param int $quality 0-100
	 * @return bool
	 * @throws Exception
	 */
	public static function imageSave($image, $filename = null, $quality = 75) {
		if (!is_resource($image)) {
			throw new Exception('Image must be a resource');
		}

		$mimes = array('image/gif', 'image/jpg', 'image/jpeg', 'image/png');
		if (in_array($filename, $mimes)) {
			$ext = str_replace('image/', '', $filename);
			header("Content-type: $filename");
			$filename = null;
		} else {
			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		}

		switch ($ext) {
			case 'gif':
				return imagegif($image, $filename);
			case 'jpg':
			case 'jpeg':
				return imagejpeg($image, $filename, $quality);
			case 'png':
				$quality = round($quality / 100 * 9);
				$quality = 9 - $quality;
				return imagepng($image, $filename, $quality);
			default:
				throw new Exception('Not supported ' . $ext);
		}
	}

	/**
	 * Resize an image, proptionnaly if needed
	 * 
	 * @param string $filename
	 * @param int|string $width
	 * @param int|string $height
	 * @param bool $proportional
	 * @param bool $output
	 * @return resource
	 */
	public static function imageResize($filename, $width = 0, $height = 0, $proportional = true, $output = false) {
		if ($width <= 0 && $height <= 0) {
			return false;
		}
		$infos = getimagesize($filename);
		$widthOld = $infos[0];
		$heightOld = $infos[1];
		$type = $infos[2];

		//handle x
		if (strpos($width, 'x') !== false) {
			$parts = explode('x', $width);
			$width = trim($parts[0]);
			$height = trim($parts[1]);
		}

		//handle %
		if (strpos($width, '%') !== false) {
			$perc = trim($width, '%');
			$width = $widthOld / 100 * $perc;
		}
		if (strpos($height, '%') !== false) {
			$perc = trim($height, '%');
			$height = $heightOld / 100 * $perc;
		}

		//for proportional resize, check the ratio
		if ($proportional) {
			if ($width == 0) {
				$ratio = $height / $heightOld;
			} elseif ($height == 0) {
				$ratio = $width / $widthOld;
			} else {
				$ratio = min($width / $widthOld, $height / $heightOld);
			}

			$width = round($widthOld * $ratio);
			$height = round($heightOld * $ratio);
		}

		$image = self::imageCreate($filename);
		$imageResized = imagecreatetruecolor($width, $height);

		//transparency support
		if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {
			$transparency = imagecolortransparent($image);
			if ($transparency && $transparency > 0) {
				$transpColor = imagecolorsforindex($image, $transparency);
				$transparency = imagecolorallocate($imageResized, $transpColor['red'], $transpColor['green'], $transpColor['blue']);
				imagefill($imageResized, 0, 0, $transparency);
				imagecolortransparent($imageResized, $transparency);
			} elseif ($type == IMAGETYPE_PNG) {
				imagealphablending($imageResized, false);
				$color = imagecolorallocatealpha($imageResized, 0, 0, 0, 127);
				imagefill($imageResized, 0, 0, $color);
				imagesavealpha($imageResized, true);
			}
		}

		imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $width, $height, $widthOld, $heightOld);

		if ($output) {
			$filename = image_type_to_mime_type($type);
		}

		return self::imageSave($imageResized, $filename);
	}

	/**
	 * Crop an image, resizing it before for optimal cropping result
	 * 
	 * @param string $filename
	 * @param int $width
	 * @param int $height
	 * @param int|string $fromX
	 * @param int|string $fromY
	 * @param bool $resize
	 * @return bool
	 * @throws Exception
	 */
	public static function imageCrop($filename, $width = 0, $height = 0, $fromX = '50%', $fromY = '50%', $resize = true) {
		$infos = getimagesize($filename);
		$widthOld = $infos[0];
		$heightOld = $infos[1];
		
		if ($resize) {
			if($widthOld < $heightOld) {
				self::imageResize($filename, $width, 0);
			}
			else {
				self::imageResize($filename, 0, $height);
			}
		}
		
		$infos = getimagesize($filename);
		$widthOld = $infos[0];
		$heightOld = $infos[1];
		$type = $infos[2];

		if ($width == 0) {
			$width = $widthOld;
		}
		if ($height == 0) {
			$height = $heightOld;
		}

		//handle x
		if (strpos($fromX, 'x') !== false) {
			$parts = explode('x', $fromX);
			$fromX = trim($parts[0]);
			$fromY = trim($parts[1]);
		}

		//handle %
		if (strpos($fromX, '%') !== false) {
			$perc = trim($fromX, '%');
			$fromX = round(max($widthOld - $width, 0) / 100 * $perc);
		}
		if (strpos($fromY, '%') !== false) {
			$perc = trim($fromY, '%');
			$fromY = round(max($heightOld - $height, 0) / 100 * $perc);
		}

		$image = self::imageCreate($filename);
		$imageResized = imagecreatetruecolor($width, $height);

		//transparency support
		if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {
			$transparency = imagecolortransparent($image);
			if ($transparency) {
				$transpColor = imagecolorsforindex($image, $transparency);
				$transparency = imagecolorallocate($imageResized, $transpColor['red'], $transpColor['green'], $transpColor['blue']);
				imagefill($imageResized, 0, 0, $transparency);
				imagecolortransparent($imageResized, $transparency);
			} elseif ($type == IMAGETYPE_PNG) {
				imagealphablending($imageResized, false);
				$color = imagecolorallocatealpha($imageResized, 0, 0, 0, 127);
				imagefill($imageResized, 0, 0, $color);
				imagesavealpha($imageResized, true);
			}
		}

		imagecopyresampled($imageResized, $image, 0, 0, $fromX, $fromY, $width, $height, $width, $height);
		return self::imageSave($imageResized, $filename);
	}

	/**
	 * Flip image
	 * @param string|resource $filename
	 * @param string $mode vertical,horizontal,both
	 * @return type
	 */
	public static function imageFlip($filename, $mode = 'vertical') {
		$image = $filename;
		if (!is_resource($image)) {
			$image = self::imageCreate($filename);
		}

		$width = imagesx($image);
		$height = imagesy($image);

		$src_x = 0;
		$src_y = 0;
		$src_width = $width;
		$src_height = $height;

		switch ($mode) {
			case 'h':
			case 'horizontal':
				$src_y = $height;
				$src_height = -$height;
				break;
			case 'v':
			case 'h':
			case 'vertical':
				$src_x = $width;
				$src_width = -$width;
				break;
			case 'b':
			case 'wh':
			case 'hw':
			case 'both':
				$src_x = $width;
				$src_y = $height;
				$src_width = -$width;
				$src_height = -$height;
				break;

			default:
				return $image;
		}

		$image_flipped = imagecreatetruecolor($width, $height);

		imagecopyresampled($image_flipped, $image, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height);
		if (!is_resource($filename)) {
			self::imageSave($image_flipped, $filename);
		}
		return $image_flipped;
	}

	/**
	 * Image auto rotate based on exif data
	 * @param string $filename Filename to be examined by exif_read_data
	 * @param resource $resource existing resource to use
	 * @return resource
	 */
	public static function imageAutoRotate($filename, $resource = null) {
		$exif = exif_read_data($filename);
		$image = $resource;
		if (!$image) {
			$image = self::imageCreate($filename);
		}
		if (!empty($exif['Orientation'])) {
			switch ($exif['Orientation']) {
				case 2: // horizontal flip
					$image = self::imageFlip($image, 'horizontal');
					break;
				case 3: // 180 rotate left
					$image = imagerotate($image, 180, 0);
					break;
				case 4: //vertical flip
					$image = self::imageFlip($image, 'vertical');
					break;
				case 5: //vertical flip + 90 rotate right
					$image = self::imageFlip($image, 'vertical');
					$image = imagerotate($image, -90);
					break;
				case 6: //90 rotate right
					$image = imagerotate($image, -90, 0);
					break;
				case 7: // horizontal flip + 90 rotate right
					$image = self::imageFlip($image, 'horizontal');
					$image = imagerotate($image, -90);
				case 8: // 90 rotate left
					$image = imagerotate($image, 90, 0);
					break;
			}
			if (!$resource) {
				self::imageSave($image, $filename);
			}
		}
		return $image;
	}

	/**
	 * Extract latitude and longitude
	 * @param string $filename
	 * @return bool|array
	 */
	public static function imageLocation($filename) {
		//get the EXIF
		$exif = exif_read_data($filename);

		if (!isset($exif['GPSLatitudeRef'])) {
			return false;
		}

		//get the Hemisphere multiplier
		$LatM = 1;
		$LongM = 1;
		if ($exif["GPSLatitudeRef"] == 'S') {
			$LatM = -1;
		}
		if ($exif["GPSLongitudeRef"] == 'W') {
			$LongM = -1;
		}

		//get the GPS data
		$gps = array();
		$gps['LatDegree'] = $exif["GPSLatitude"][0];
		$gps['LatMinute'] = $exif["GPSLatitude"][1];
		$gps['LatgSeconds'] = $exif["GPSLatitude"][2];
		$gps['LongDegree'] = $exif["GPSLongitude"][0];
		$gps['LongMinute'] = $exif["GPSLongitude"][1];
		$gps['LongSeconds'] = $exif["GPSLongitude"][2];

		//convert strings to numbers
		foreach ($gps as $key => $value) {
			$pos = strpos($value, '/');
			if ($pos !== false) {
				$temp = explode('/', $value);
				$gps[$key] = $temp[0] / $temp[1];
			}
		}

		//calculate the decimal degree
		$result = array();
		$result['lat'] = $LatM * ($gps['LatDegree'] + ($gps['LatMinute'] / 60) + ($gps['LatgSeconds'] / 3600));
		$result['lng'] = $LongM * ($gps['LongDegree'] + ($gps['LongMinute'] / 60) + ($gps['LongSeconds'] / 3600));

		return $result;
	}

}