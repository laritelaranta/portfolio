<?php 

// Creates a pdf file from employee information

include("includes/security.php");

$row_tyonhakija = mysql_fetch_array(mysql_query("SELECT * FROM employees WHERE tyonhakija_id = ".$_GET['id'].""));
$row_minicv = mysql_fetch_array(mysql_query("SELECT * FROM minicv WHERE tyonhakija_id = ".$_GET['id'].""));
$q_koulutus = mysql_query("SELECT * FROM minicv_koulutus WHERE tyonhakija_id = ".$_GET['id']."");
$q_tyokokemus = mysql_query("SELECT * FROM minicv_tyokokemus WHERE tyonhakija_id = ".$_GET['id']."");
$q_languages = mysql_query("SELECT * FROM employees_languages WHERE tyonhakija_id = ".$_GET['id']."");
				
require('fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica','B',10);
$pdf->Image('images/logo.jpg');
$pdf->Ln(15);
$pdf->Cell(40,10,'Hakijan tiedot');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
$pdf->Cell(30,10,'Etunimi:');
$pdf->Cell(70,10,''.utf8_decode($row_tyonhakija["etunimi"]).'');
$pdf->Cell(25,10,'Sukunimi:');
$pdf->Cell(50,10,''.utf8_decode($row_tyonhakija["sukunimi"]).'');
$pdf->Ln(5);
$pdf->Cell(30,10,'Syntymäaika:');
$pdf->Cell(50,10,''.utf8_decode($row_minicv["syntymaaika"]).'', 0, 'L');
$pdf->Ln(15);
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(30,5,'Haastattelijan kommentit');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
$pdf->MultiCell(170,5,''.utf8_decode($row_minicv["kommentit"]).'', 0, 'L');
$pdf->Ln(5);
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(40,10,'Työkokemus');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','I',8);
$pdf->Cell(45,10,'Työnantaja');
$pdf->Cell(40,10,'Työtehtävä');
$pdf->Cell(32,10,'Työsuhteen kesto (kk)');
$pdf->Cell(35,10,'Työsuhteen alkamispäivä');
$pdf->Cell(35,10,'Työsuhteen päättymispäivä');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
while($row_tyokokemus = mysql_fetch_array($q_tyokokemus)) {
$pdf->Cell(45,4,''.utf8_decode(substr($row_tyokokemus["tyonantaja"], 0, 30)).'');
$pdf->Cell(40,4,''.utf8_decode(substr($row_tyokokemus["tyotehtava"], 0, 30)).'');
$pdf->Cell(32,4,''.utf8_decode($row_tyokokemus["tyosuhde_kesto"]).'');
$pdf->Cell(35,4,''.utf8_decode($row_tyokokemus["tyosuhde_alku"]).'');
$pdf->Cell(35,4,''.utf8_decode($row_tyokokemus["tyosuhde_loppu"]).'');
$pdf->Ln(5);
}
$pdf->Ln(5);
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(40,10,'Koulutus');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','I',8);
$pdf->Cell(45,10,'Tutkinto');
$pdf->Cell(55,10,'Koulu');
$pdf->Cell(37,10,'Koulutuksen alkamispäivä');
$pdf->Cell(37,10,'Koulutuksen päättymispäivä');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
while($row_koulutus = mysql_fetch_array($q_koulutus)) {
$pdf->Cell(45,4,''.utf8_decode(substr($row_koulutus["tutkinto"], 0, 35)).'');
$pdf->Cell(55,4,''.utf8_decode(substr($row_koulutus["koulu"], 0, 35)).'');
$pdf->Cell(37,4,''.utf8_decode($row_koulutus["koulutus_alku"]).'');
$pdf->Cell(37,4,''.utf8_decode($row_koulutus["koulutus_loppu"]).'');
$pdf->Ln(5);
}
$pdf->Ln(5);
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(40,10,'languages');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','I',8);
$pdf->Cell(45,10,'Kieli');
$pdf->Cell(50,10,'Taitotaso');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
while($row_languages = mysql_fetch_array($q_languages)) {
if($row_languages["kieli_id"]) {
	$kieli = mysql_result(mysql_query("SELECT kieli FROM employees_kielet WHERE kieli_id = ".$row_languages['kieli_id'].""),0);
} else {
	$kieli = $row_languages["muu_kieli"];
}
$pdf->Cell(45,4,''.utf8_decode(substr($kieli, 0, 35)).'');
$pdf->Cell(50,4,''.utf8_decode(substr($row_languages["taitotaso"], 0, 35)).'');
$pdf->Ln(5);
}
$pdf->Ln(7);
if($row_minicv["muu_kokemus"]) {
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell(30,5,'Muu kokemus');
$pdf->Ln(8);
$pdf->SetFont('Helvetica','',8);
$pdf->MultiCell(170,5,''.utf8_decode($row_minicv["muu_kokemus"]).'', 0, 'L');
}
$pdf->Output();

?>
