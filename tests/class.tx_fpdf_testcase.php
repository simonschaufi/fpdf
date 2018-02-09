<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 David Bruehlmeier (typo3@bruehlmeier.com)
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

require_once(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');

/**
 * FPDF and FPDI testcases
 *
 * WARNING!!: Never ever run a unit test like this on a live site!!
 *
 *
 * @author	David Bruehlmeier <typo3@bruehlmeier.com>
 */
class tx_fpdf_testcase extends tx_t3unit_testcase {

	/*********************************************************
	 *
	 * READING TESTS
	 *
	 *********************************************************/

	public function test_createBasicPDF() {
		$orientation = 'portrait';
		$unit = 'mm';
		$format = 'A4';
		$font = 'courier';
		$fontSize = 12;
		$filePath = t3lib_extMgm::extPath('fpdf').'tests/results/test_createBasicPDF_'.time().'.pdf';

		$pdf = new PDF($orientation, $unit, $format);
		$pdf->SetMargins(10, 10, 10);
		$pdf->AddPage();
		$pdf->SetFont($font,'',$fontSize);
		$pdf->Cell(100,40,'test_createBasicPDF','B', 1);
		$pdf->Write(20, 'This is a A4-portrait doc with 10mm margins in courier (12pt).');
		$pdf->AddPage();
		$pdf->Write(20, 'Second page.');
		$pdf->Output($filePath);

		self::assertTrue(is_file($filePath));
	}

	public function test_createPDFwithTemplate() {
		$orientation = 'portrait';
		$unit = 'mm';
		$format = 'A4';
		$font = 'times';
		$fontSize = 12;
		$filePath = t3lib_extMgm::extPath('fpdf').'tests/results/test_createPDFwithTemplate_'.time().'.pdf';
		$templatePath = t3lib_extMgm::extPath('fpdf').'tests/TYPO3.pdf';

		$pdf = new PDF($orientation, $unit, $format);
		$pdf->tx_fpdf->template = $templatePath;
		$pdf->SetMargins(10, 10, 10);
		$pdf->AddPage();
		$pdf->SetFont($font,'',$fontSize);
		$pdf->Cell(100,40,'test_createPDFwithTemplate','B', 1);
		$pdf->Write(15, 'This PDF is with the Times font and based on a template with the TYPO3 logo.');
		$pdf->AddPage();
		$pdf->Write(20, 'Second page.');
		$pdf->Output($filePath);

		self::assertTrue(is_file($filePath));
	}

}
