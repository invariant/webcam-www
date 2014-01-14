<?php
function brightness_at($resource, $x, $y) {
	$colors = imagecolorsforindex($resource, imagecolorat($resource, $x, $y));
	$val = ($colors['red']/255.0 + $colors['green']/255.0 + $colors['blue']/255.0) / 3.0;
	return $val;
}

function pixel_diff($a, $b, $x, $y) {
	$colorsa = imagecolorsforindex($a, imagecolorat($a, $x, $y));
	$colorsb = imagecolorsforindex($b, imagecolorat($b, $x, $y));	
	$dr = ($colorsa['red']-$colorsb['red'])/255.0;
	$dg = ($colorsa['green']-$colorsb['green'])/255.0;
	$db = ($colorsa['blue']-$colorsb['blue'])/255.0;
	return ($dr * $dr + $dg * $dg + $db * $db)/3.0;
}

function do_diff($this_image, $prev_image) {
	$width=imagesx($this_image);
	$height=imagesy($this_image);
	$width_prev=imagesx($prev_image);
	$height_prev=imagesy($prev_image);		

	$threshold=0.075;	
	$step=10;
	$difftsum=0;	
	$brtsum=0;						
	$points=0;													
	if ($width>0 && $height >0 && $width_prev==$width && $height_prev==$height) {

		for ($x=intval($step/2); $x<$width; $x+=$step) {
			for ($y=intval($step/2); $y<$height; $y+=$step) {
				$points+=1;
				$brtsum+=brightness_at($this_image, $x, $y);
				$diff=pixel_diff($prev_image, $this_image, $x, $y);
				if ($diff>$threshold) {
					$difftsum+=$diff;								
				}		
			}
		}
		$brtsum=intval(($brtsum/$points)*1000000);
		$difftsum=intval(($difftsum/$points)*1000000);										
		echo "$width x $height, threshold $threshold, points $points\n---\n";
		echo "difference $difftsum\nbrightness $brtsum\n";
		echo "\n";	
		return array('difference'=>$difftsum, 'brightness'=>$brtsum);
	}	
}	

?>