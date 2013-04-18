<?php

$bedX = 205;
$midpointX = $bedX / 2;
$centerX = $bedX / 2;

$bedY = 205;
$midpointY = $bedY / 2;
$centerY =  $bedY / 2;

$difference = 15;

$amplitude = $difference;
$wavelenght = 2;
$waves = 2;

$layerheight = 0.2;
$fromLayer = 7;
//$toLayer = 20  ;
$toLayer = 2000	;

//$maxZ = 111;
$newZ = 0.2;
$Z = 0.6;
$E = 0;
$X = 0;
$Y = 0;
$zScale = 1.2;
$line_nr = 0;
$startEffect = false;


$style = "full";
//$styleafwijking = "bitmap";
//$styleafwijking = "bitmapfine";
$styleafwijking = "linear";
//$styleafwijking = "sinus";

$fName = "wave";
//$fName = "wave.gcode";
$lines = file($fName.".gcode");
//$lines2 = file($fName);
//print_r($lines);

//$fopen("wave.gcode","r");

///print $fromLayer;
//print $toLayer;

$layer = 0;
$Z = "";
foreach($lines as $line)
{
	If (substr($line, 0, 7) == ";LAYER:") {
		$layer = substr($line, 7);
//		print $layer . "xxxxxxxxxxxxxxxxxxxxxxxx";
	}

	If (substr($line, 0, 2) == "G1" && $layer > 1) {
		if(my_ereg("Z[ ]*(-?[0-9.]+)",$line,$regs))
			$Z = $regs[1];
	}
//	print $Z1 . "Z<BR>";
//	print $X1 . "X<BR>";
	$maxZ = $Z;
}

//print ";maxZ : " . $maxZ . "<BR>";
//print ";layers : " . $layer . "<BR>";


// print settings in file
$Z = 0; //reset z value
$layer = 0; // reset layer value

$file = fopen($fName."_waved.gcode", "w");

$commentstart = "; settings used in wave script <br>";
print $commentstart;
fwrite($file, substr($commentstart, 0, -4)."\n");

print_var_name($file);


foreach($lines as $line)
{
	If (substr($line, 0, 7) == ";LAYER:") {
		$layer = substr($line, 7);
	}
	if ($layer > $fromLayer && $layer < $toLayer) {
//	if ($layer > $fromLayer) {
	//	print "binnen";
	//	break;
		$startEffect = true;
	}
	if ($layer > $toLayer) {
		$startEffect = false;
	}
	
// print $startEffect;

if ($startEffect == true) {
//if ($layer < 30) {
//  $line_nr++; // keep track of the line_nr

	If (substr($line, 0, 2) == "G1" && $layer > 1) {
//	If (substr($line, 0, 2) == "G1") {
	  if(my_ereg("X[ ]*(-?[0-9.]+)",$line,$regs))
		$X = $regs[1];
	  if(my_ereg("Y[ ]*(-?[0-9.]+)",$line,$regs))
		$Y = $regs[1];
	  if(my_ereg("Z[ ]*(-?[0-9.]+)",$line,$regs))
		$Z = $regs[1];
	  if(my_ereg("E[ ]*(-?[0-9.]+)",$line,$regs))
		$E = $regs[1];
	  if(my_ereg("F[ ]*(-?[0-9.]+)",$line,$regs))
		$F = $regs[1];

	$im = imagecreatefrompng("platform.png");
	$rgb = imagecolorat($im, $X, $Y);

	// get average height from image
	$imwidth = imagesx ($im);
	// width in scale to platform an height	

//ongeveer	
	$imX = $imwidth*$X/$bedX;
	$imY = $imwidth*$Y/$bedY;

//precies
	$imX1 = floor($imwidth*$X/$bedX);
	$imX2 = ceil($imwidth*$X/$bedX);
	$imY1 = floor($imwidth*$Y/$bedY);
	$imY2 = ceil($imwidth*$Y/$bedY);

	$rgb = imagecolorat($im, $imX, $imY);
	$rgb1 = imagecolorat($im, $imX1, $imY1);
	$rgb2 = imagecolorat($im, $imX2, $imY2);

	
	
	$r = ($rgb >> 16) & 0xFF;
	$r1 = ($rgb1 >> 16) & 0xFF;
	$r2 = ($rgb2 >> 16) & 0xFF;

	$X1 = floor($X);
	$X2 = ceil($X);

	switch ($styleafwijking) {
		case "bitmapfine";
			//	$afwijking = ($r1+$r2)/2/255;
			if ($r1 > $r2){
				$afwijking = ($r2+((($r2-$r1)*(1-($X - $X1)))))/255;
			} else {
				$afwijking = ($r1+((($r2-$r1)*($X - $X1))))/255;
			}
			break;
		
		case "bitmap";
				$afwijking = $r1/255;
			break;

		case "linear";
				$afwijking = ($bedX-$X)/$bedX;
			break;
		
		
		case "sinus";
//			$afwijking = ((sin($waves*GetRad($X-$midpointX, $Y-$midpointY))+1)*0.5);
//			$afwijking = ((sin($waves*GetRad($X-$midpointX-20, $Y-$midpointY))+1)*0.5);
			$afwijking = ((sin($waves*GetRad($X, $Y-$midpointY))+1)*0.5);
		//print $afwijking . " - " . $Z . " - ";
			break;
	}

	switch ($style) {
		case "full":
			$newZ = ($Z * $zScale) + $afwijking*$amplitude*$Z/$maxZ-$afwijking-$layerheight;
//			$newZ = ($Z * $zScale) + $afwijking*$amplitude*$Z/$maxZ;
			$newZ = round($newZ, 3);
			$newE = $E + $afwijking*0.3*$Z/$maxZ;
			$newE = round($newE, 3);
			break;
	
		case "half":
			if ($Z < $maxZ/2){
				$newZ = ($Z * $zScale) + $afwijking*$amplitude/2*$Z/$maxZ;
				$newE = $E + $afwijking*0.3*$Z/$maxZ;
			} else {
				$newZ = ($Z * $zScale) + $afwijking*$amplitude/2*($maxZ-$Z)/$maxZ;
				$newE = $E + $afwijking*0.3*($maxZ-$Z)/$maxZ;
			}		
			break;
	}
		
		
		$newLine = "G1 X" . $X . " Y". $Y . " Z". $newZ; 
//		$newLine = "G1 X" . $X . " Y". $Y . " Z". $Z; 
		if ($E != "") {
//			$newLine .= " E". $newE;
			$newLine .= " E". $E;
			$E = "";
		}
		if ($F != "") {
			$newLine .= " F". $F;
			$F = "";
		}
		$newLine .= "; afwijking: ". $afwijking. " zOld: " . $Z . "<br>";
		print $newLine;
		fwrite($file, substr($newLine, 0, -4)."\n");

		//print "check<br>";

	} else {
		print $line . "<br>";
		fwrite($file, $line);
	}

} else {
		print $line . "<br>";
	}
}

fclose($file);



function GetRad($x, $y)
{
  // we don't want to cause division by zero
  if($x == 0) $x = 1 / 10000;

//  $rad = atan(abs($y / $x));
  $rad = atan(($y / $x));
  return $rad;
     
}

function GetDegree($x, $y)
{
  // we don't want to cause division by zero
  if($x == 0) $x = 1 / 10000;
     
  $deg = rad2deg(atan(abs($y / $x)));
//  $deg = rad2deg(atan(($y / $x)));
     
  if($y >= 0) $deg = $x < 0 ? 180 - $deg : $deg;
  else        $deg = $x < 0 ? 180 + $deg : 360 - $deg;
     
  return $deg;
     
}

function my_ereg($reg,$str,&$regs)
{
  return @ereg($reg,$str,$regs);
}

function logsettings($var)
{
	$logsetting .= print_var_name($var) . " : " . $var . "<br>";
	return $logsetting;
}

function print_var_name($file) {
//function print_var_name($var) {
    foreach($GLOBALS as $var_name => $value) {
        if (substr($var_name, 0, 1) != "_" && substr($var_name, 0, 4) != "HTTP" && $var_name != "GLOBALS" && $var_name != "line" && $var_name != "regs") {
			$commentvar = ";" . $var_name . " : " . $value . "<br>";
			print $commentvar;
			fwrite($file, substr($commentvar, 0, -4)."\n");
//            return $var_name;
        }
    }

    return false;
}

?>
