<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Peter Klein (peter@umloud.dk)
*  (c) 2005 David Bruehlmeier (typo3@bruehlmeier.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
* ------- Description -------
* Extension of the FPDI class. The method Header() is implemented to
* allow the inclusion of template files. The template file to be used
* can be set by the class variable $this->tx_fpdf->template
* (full path required).
*
* You can include this class in your scripts to get the full
* functionality of FPDF and FPDI, including support for custom
* fonts and custom template files.
*
* @author Peter Klein <peter@umloud.dk>
* @author David Bruehlmeier <typo3@bruehlmeier.com>
*/

require_once(t3lib_extMgm::extPath('fpdf').'fpdi.php');

class PDF extends FPDI	{

	var $SpotColors=array();
	var $outlines=array();
	var $OutlineRoot;
	var $angle=0;

	function Header()		{
		if ($this->tx_fpdf->template)		{
			$this->setSourceFile($this->tx_fpdf->template);
			$tplidx = $this->ImportPage(1);
			$this->useTemplate($tplidx);
		}
	}
	/**
	*
	* Bookmark() - Adds bookmark support
	*
	* @param	string	txt: the bookmark title. (The title must be encoded in ISO-8859-1.)
	* @param	int		level: the bookmark level (0 is top level, 1 is just below, and so on).
	* @param	float	y: the y position of the bookmark destination in the current page. -1 means the current position. Default value: 0.
	*
	*/
	function Bookmark($txt, $level=0, $y=0) {
	    if($y==-1) $y=$this->GetY();
	    $this->outlines[]=array('t'=>$txt, 'l'=>$level, 'y'=>($this->h-$y)*$this->k, 'p'=>$this->PageNo());
	}

	/**
	*
	* BookmarkUTF8() - Adds bookmark support
	*
	* @param	string	txt: the bookmark title.
	* @param	int		level: the bookmark level (0 is top level, 1 is just below, and so on).
	* @param	float	y: the y position of the bookmark destination in the current page. -1 means the current position. Default value: 0.
	*
	*/
	function BookmarkUTF8($txt, $level=0, $y=0) {
	    if(!function_exists('iconv')) $this->Error('iconv extension not available');
	    $txt=iconv('UTF-8','UTF-16BE',$txt);
	    if($txt===false) $this->Error('The string could not be converted');
	    $this->Bookmark("\xFE\xFF".$txt,$level,$y);
	}

	/**
	*
	* CreateIndex() - Prints an index from the created bookmarks.
	*
	*/
	function CreateIndex(){
	    //Index title
	    $this->SetFontSize(20);
	    $this->Cell(0,5,'Index',0,1,'C');
	    $this->SetFontSize(15);
	    $this->Ln(10);

	    $size=sizeof($this->outlines);
	    $PageCellSize=$this->GetStringWidth('p. '.$this->outlines[$size-1]['p'])+2;
	    for ($i=0;$i<$size;$i++){
	        //Offset
	        $level=$this->outlines[$i]['l'];
	        if($level>0)
	            $this->Cell($level*8);

	        //Caption
	        $str=$this->outlines[$i]['t'];
	        $strsize=$this->GetStringWidth($str);
	        $avail_size=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-4;
	        while ($strsize>=$avail_size){
	            $str=substr($str,0,-1);
	            $strsize=$this->GetStringWidth($str);
	        }
	        $this->Cell($strsize+2,$this->FontSize+2,$str);

	        //Filling dots
	        $w=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-($strsize+2);
	        $nb=$w/$this->GetStringWidth('.');
	        $dots=str_repeat('.',$nb);
	        $this->Cell($w,$this->FontSize+2,$dots,0,0,'R');

	        //Page number
	        $this->Cell($PageCellSize,$this->FontSize+2,'p. '.$this->outlines[$i]['p'],0,1,'R');
	    }
	}

	/**
	*
	* Rotate() - Perform a rotation around a given center.
	*
	* @param	float	angle: angle in degrees.
	* @param	float	x: abscissa of the rotation center. Default value: current position.
	* @param	float	y: ordinate of the rotation center. Default value: current position.
	*
	*/
	function Rotate($angle, $x=-1, $y=-1) {
		if($x==-1)
			$x=$this->x;
		if($y==-1)
			$y=$this->y;
		if($this->angle!=0)
			$this->_out('Q');
		$this->angle=$angle;
		if($angle!=0) {
			$angle*=M_PI/180;
			$c=cos($angle);
			$s=sin($angle);
			$cx=$x*$this->k;
			$cy=($this->h-$y)*$this->k;
			$this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
		}
	}


	/**
	*
	* AddSpotColor() - Allows use of spot colors (Pantone) for use in professional printing.
	*
	* @param	string	name: name of spotcolor.
	* @param	int		c: CMYK colordefinition cyan.
	* @param	int		m: CMYK colordefinition magenta.
	* @param	int		y: CMYK colordefinition yellow.
	* @param	int		k: CMYK colordefinition black.
	*
	*/

	function AddSpotColor($name,$c,$m,$y,$k) {
		if (!isset($this->SpotColors[$name])) {
			$i=count($this->SpotColors)+1;
			$this->SpotColors[$name]=array('i'=>$i,'c'=>$c,'m'=>$m,'y'=>$y,'k'=>$k);
		}
	}
	/**
	*
	* SetDrawColor() - Set drawing color.
	*
	* With 1 parameter:
	* @param	int		grey: Value between 0 and 255 (0 = black, 255 = white)
	* Or:
	* @param	string	spotcolor: spotcolor name.
	*
	* With 2 parameters:
	* @param	string	spotcolor: spotcolor name.
	* @param	int		tint: intensity of the color (100 by default, i.e. full intensity).
	*
	* With 3 parameters:
	* @param	int		r: RGB colordefinition red.
	* @param	int		g: RGB colordefinition green.
	* @param	int		b: RGB colordefinition blue.
	*
	* With 4 parameters:
	* @param	int		c: CMYK colordefinition cyan.
	* @param	int		m: CMYK colordefinition magenta.
	* @param	int		y: CMYK colordefinition yellow.
	* @param	int		k: CMYK colordefinition black.
	*
	*  SetFillColor and SetTextColor same as SetDrawColor
	*
	*/
    function SetDrawColor() {

        //Set color for all stroking operations
        switch(func_num_args()) {
            case 1:
                $g = func_get_arg(0);
				$this->DrawColor = is_int($g) ? sprintf('%.3f G', $g / 255) : $this->_setspotcolor2($g);
                break;
			case 2:
				$n = func_get_arg(0);
				$t = func_get_arg(1);
				$this->DrawColor = $this->_setspotcolor2($n,$t);
				break;
            case 3:
                $r = func_get_arg(0);
                $g = func_get_arg(1);
                $b = func_get_arg(2);
                $this->DrawColor = sprintf('%.3f %.3f %.3f RG', $r / 255, $g / 255, $b / 255);
                break;
            case 4:
                $c = func_get_arg(0);
                $m = func_get_arg(1);
                $y = func_get_arg(2);
                $k = func_get_arg(3);
                $this->DrawColor = sprintf('%.3f %.3f %.3f %.3f K', $c / 100, $m / 100, $y / 100, $k / 100);
                break;
            default:
                $this->DrawColor = '0 G';
        }
        if($this->page > 0)
            $this->_out($this->DrawColor);
    }

    function SetFillColor() {

        //Set color for all filling operations
        switch(func_num_args()) {
            case 1:
                $g = func_get_arg(0);
                $this->FillColor = is_int($g) ? sprintf('%.3f g', $g / 255) :  $this->_setspotcolor($g);
                break;
			case 2:
				$n = func_get_arg(0);
				$t = func_get_arg(1);
				$this->FillColor = $this->_setspotcolor($n,$t);
				break;
            case 3:
                $r = func_get_arg(0);
                $g = func_get_arg(1);
                $b = func_get_arg(2);
                $this->FillColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
                break;
            case 4:
                $c = func_get_arg(0);
                $m = func_get_arg(1);
                $y = func_get_arg(2);
                $k = func_get_arg(3);
                $this->FillColor = sprintf('%.3f %.3f %.3f %.3f k', $c / 100, $m / 100, $y / 100, $k / 100);
                break;
            default:
                $this->FillColor = '0 g';
        }
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if($this->page > 0)
            $this->_out($this->FillColor);
    }

    function SetTextColor() {

        //Set color for text
        switch(func_num_args()) {
            case 1:
                $g = func_get_arg(0);
                $this->TextColor = is_int($g) ? sprintf('%.3f g', $g / 255) : $this->_setspotcolor($g);
                break;
			case 2:
				$n = func_get_arg(0);
				$t = func_get_arg(1);
				$this->TextColor = $this->_setspotcolor($n,$t);
				break;
            case 3:
                $r = func_get_arg(0);
                $g = func_get_arg(1);
                $b = func_get_arg(2);
                $this->TextColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
                break;
            case 4:
                $c = func_get_arg(0);
                $m = func_get_arg(1);
                $y = func_get_arg(2);
                $k = func_get_arg(3);
                $this->TextColor = sprintf('%.3f %.3f %.3f %.3f k', $c / 100, $m / 100, $y / 100, $k / 100);
                break;
            default:
                $this->TextColor = '0 g';
        }
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

	function _setspotcolor2($name,$tint=100) {
		if (!isset($this->SpotColors[$name])) $this->Error('Undefined spot color: '.$name);
		return sprintf('/CS%d CS %.3f SCN',$this->SpotColors[$name]['i'],$tint/100);
	}

	function _setspotcolor($name,$tint=100) {
		if (!isset($this->SpotColors[$name])) $this->Error('Undefined spot color: '.$name);
		return sprintf('/CS%d cs %.3f scn',$this->SpotColors[$name]['i'],$tint/100);
	}

	function _putspotcolors() {
		foreach($this->SpotColors as $name=>$color) {
			$this->_newobj();
			$this->_out('[/Separation /'.str_replace(' ','#20',$name));
			$this->_out('/DeviceCMYK <<');
			$this->_out('/Range [0 1 0 1 0 1 0 1] /C0 [0 0 0 0] ');
			$this->_out(sprintf('/C1 [%.4f %.4f %.4f %.4f] ',$color['c']/100,$color['m']/100,$color['y']/100,$color['k']/100));
			$this->_out('/FunctionType 2 /Domain [0 1] /N 1>>]');
			$this->_out('endobj');
			$this->SpotColors[$name]['n']=$this->n;
		}
	}

	function _putbookmarks() {
	    $nb=count($this->outlines);
	    if ($nb==0) return;
	    $lru=array();
	    $level=0;
	    foreach($this->outlines as $i=>$o) {
	        if ($o['l']>0) {
	            $parent=$lru[$o['l']-1];
	            //Set parent and last pointers
	            $this->outlines[$i]['parent']=$parent;
	            $this->outlines[$parent]['last']=$i;
	            if ($o['l']>$level) {
	                //Level increasing: set first pointer
	                $this->outlines[$parent]['first']=$i;
	            }
	        }
	        else $this->outlines[$i]['parent']=$nb;
	        if ($o['l']<=$level and $i>0) {
	            //Set prev and next pointers
	            $prev=$lru[$o['l']];
	            $this->outlines[$prev]['next']=$i;
	            $this->outlines[$i]['prev']=$prev;
	        }
	        $lru[$o['l']]=$i;
	        $level=$o['l'];
	    }
	    //Outline items
	    $n=$this->n+1;
	    foreach($this->outlines as $i=>$o) {
	        $this->_newobj();
	        $this->_out('<</Title '.$this->_textstring($o['t']));
	        $this->_out('/Parent '.($n+$o['parent']).' 0 R');
	        if(isset($o['prev'])) $this->_out('/Prev '.($n+$o['prev']).' 0 R');
	        if(isset($o['next'])) $this->_out('/Next '.($n+$o['next']).' 0 R');
	        if(isset($o['first'])) $this->_out('/First '.($n+$o['first']).' 0 R');
	        if(isset($o['last'])) $this->_out('/Last '.($n+$o['last']).' 0 R');
	        $this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]',1+2*$o['p'],$o['y']));
	        $this->_out('/Count 0>>');
	        $this->_out('endobj');
	    }
	    //Outline root
	    $this->_newobj();
	    $this->OutlineRoot=$this->n;
	    $this->_out('<</Type /Outlines /First '.$n.' 0 R');
	    $this->_out('/Last '.($n+$lru[0]).' 0 R>>');
	    $this->_out('endobj');
	}

	function _putcatalog() {
	    parent::_putcatalog();
	    if (count($this->outlines)>0) {
	        $this->_out('/Outlines '.$this->OutlineRoot.' 0 R');
	        $this->_out('/PageMode /UseOutlines');
	    }
	}

	function _escape($s) {
	    $s=str_replace('\\','\\\\',$s);
	    $s=str_replace(')','\\)',$s);
	    $s=str_replace('(','\\(',$s);
	    $s=str_replace("\r",'\\r',$s);
	    return $s;
	}


	function _putcolorresourcedict() {
		if (count($this->SpotColors)) {
			$this->_out('/ColorSpace <<');
			foreach($this->SpotColors as $color)
				$this->_out('/CS'.$color['i'].' '.$color['n'].' 0 R');
           	$this->_out('>>');
		}
	}

	/**
	 * Put resources
	 */
	function _putresources() {
		$this->_putfonts();
		$this->_putimages();
		$this->_putbookmarks();
		$this->_putspotcolors();
		$this->_putformxobjects();
		$this->_putimportedobjects();
			//Resource dictionary
		$this->offsets[2]=strlen($this->buffer);
		$this->_out('2 0 obj');
		$this->_out('<<');
		$this->_putresourcedict();
		$this->_putcolorresourcedict();
		$this->_out('>>');
		$this->_out('endobj');
	}


	function _endpage() {
	    if ($this->angle!=0) {
			$this->angle=0;
			$this->_out('Q');
		}
		parent::_endpage();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fpdf/class.tx_fpdf.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fpdf/class.tx_fpdf.php']);
}
