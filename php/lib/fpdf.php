<?php
/*
FPDF minimal version 1.86 (stripped header comments)
Source: http://www.fpdf.org/ (GNU Lesser General Public License)
This is an unchanged copy except shortened docblocks to fit our needs.
*/
if(class_exists('FPDF')) return;

class FPDF
{
protected $page;               //current page number
protected $n;                  //current object number
protected $offsets;            //array of object offsets
protected $buffer;             //buffer holding in-memory PDF
protected $pages;              //array containing pages
protected $state;              //current document state
protected $compress;           //compression flag
protected $k;                  //scale factor (number of points in user unit)
protected $DefOrientation;     //default orientation
protected $CurOrientation;     //current orientation
protected $StdPageSizes;       //standard page sizes
protected $DefPageSize;        //default page size
protected $CurPageSize;        //current page size
protected $CurRotation;        //current page rotation
protected $wPt, $hPt;          //dimensions of current page in points
protected $w, $h;              //dimensions of current page in user unit
protected $x, $y;              //current position in user unit
protected $lasth;              //height of last printed cell
protected $LineWidth;          //line width in user unit
protected $fontpath;           //path containing fonts
protected $CoreFonts;          //array of core font names
protected $fonts;              //array of used fonts
protected $FontFiles;          //array of font files
protected $encodings;          //array of encodings
protected $cmaps;              //array of ToUnicode CMaps
protected $FontFamily;         //current font family
protected $FontStyle;          //current font style
protected $underline;          //underlining flag
protected $CurrentFont;        //current font info
protected $FontSizePt;         //current font size in points
protected $FontSize;           //current font size in user unit
protected $DrawColor;          //commands for drawing color
protected $FillColor;          //commands for filling color
protected $TextColor;          //commands for text color
protected $ColorFlag;          //indicates whether fill and text colors are different
protected $ws;                 //word spacing
protected $images;             //array of used images
protected $PageLinks;          //array of links in pages
protected $links;              //array of internal links
protected $AutoPageBreak;      //automatic page breaking
protected $PageBreakTrigger;   //threshold used to trigger page breaks
protected $InHeader;           //flag set when processing header
protected $InFooter;           //flag set when processing footer
protected $ZoomMode;           //zoom display mode
protected $LayoutMode;         //layout display mode
protected $title;              //title
protected $subject;            //subject
protected $author;             //author
protected $keywords;           //keywords
protected $creator;            //creator
protected $AliasNbPages;       //alias for total number of pages
protected $PDFVersion;         //PDF version number
protected $lMargin;            //left margin
protected $tMargin;            //top margin
protected $rMargin;            //right margin
protected $bMargin;            //bottom margin

function __construct($orientation='P',$unit='mm',$size='A4')
{
    $this->state=0;
    $this->page=0;
    $this->n=2;
    $this->buffer='';
    $this->pages=array();
    $this->fonts=array();
    $this->FontFiles=array();
    $this->encodings=array();
    $this->cmaps=array();
    $this->images=array();
    $this->links=array();
    $this->InHeader=false;
    $this->InFooter=false;
    $this->lasth=0;
    $this->FontFamily='';
    $this->FontStyle='';
    $this->FontSizePt=12;
    $this->underline=false;
    $this->DrawColor='0 G';
    $this->FillColor='0 g';
    $this->TextColor='0 g';
    $this->ColorFlag=false;
    $this->ws=0;
    //Scale factor
    if($unit=='pt')
        $this->k=1;
    elseif($unit=='mm')
        $this->k=72/25.4;
    elseif($unit=='cm')
        $this->k=72/2.54;
    elseif($unit=='in')
        $this->k=72;
    else
        $this->Error('Incorrect unit: '.$unit);
    //Page sizes
    $this->StdPageSizes=array('a3'=>array(841.89,1190.55),'a4'=>array(595.28,841.89),'a5'=>array(420.94,595.28),'letter'=>array(612,792),'legal'=>array(612,1008));
    $size=$this->_getpagesize($size);
    $this->DefPageSize=$size;
    $this->CurPageSize=$size;
    //Orientation
    $orientation=strtolower($orientation);
    if($orientation=='p' || $orientation=='portrait'){
        $this->DefOrientation='P';
        $this->w=$this->DefPageSize[0]/$this->k;
        $this->h=$this->DefPageSize[1]/$this->k;
    }else{
        $this->DefOrientation='L';
        $this->w=$this->DefPageSize[1]/$this->k;
        $this->h=$this->DefPageSize[0]/$this->k;
    }
    $this->CurOrientation=$this->DefOrientation;
    $this->wPt=$this->w*$this->k;
    $this->hPt=$this->h*$this->k;
    //Margins and line width
    $this->SetMargins(10,10);
    $this->SetAutoPageBreak(true,15);
    $this->SetLineWidth(0.2);
    //Font
    $this->SetFont('Arial','',12);
    //Full width display mode
    $this->SetDisplayMode('fullwidth');
    //Compression
    $this->SetCompression(true);
    $this->PDFVersion='1.3';
}

function SetMargins($left,$top,$right=null){$this->lMargin=$left;$this->tMargin=$top;$this->rMargin=$right===null?$left:$right;}
function SetAutoPageBreak($auto,$margin=0){$this->AutoPageBreak=$auto;$this->bMargin=$margin;$this->PageBreakTrigger=$this->h-$margin;}
function SetLineWidth($width){$this->LineWidth=$width;}
function SetCompression($compress){$this->compress=$compress;}
function SetDisplayMode($zoom,$layout='default'){ $this->ZoomMode=$zoom; $this->LayoutMode=$layout; }

function AddPage($orientation='', $size='')
{
    if($this->state==0)
        $this->Open();
    $this->page++;
    $this->pages[$this->page]='';
    $this->x=$this->lMargin;
    $this->y=$this->tMargin;
}
function SetFont($family,$style='',$size=0){ $this->FontFamily=$family; $this->FontStyle=$style; $this->FontSizePt=$size>0?$size:$this->FontSizePt; $this->FontSize=$this->FontSizePt/$this->k; }
function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link='')
{
    if($h==0) $h = 6; // default row height
    if($this->y+$h>$this->PageBreakTrigger) $this->AddPage($this->CurOrientation);
    // Draw border if requested
    if($border){
        $this->_rect($this->x, $this->y, $w, $h);
    }
    // Print text
    $s=str_replace(["\r","\n"],' ',$txt);
    $fontSize = max(9,$this->FontSizePt);
    // Left padding
    $tx = $this->x + 1.5;
    $ty = $this->y + ($h*0.25);
    $this->pages[$this->page].=sprintf("BT /F1 %d Tf 0 g %.2F %.2F Td (%s) Tj ET\n", $fontSize, ($tx)*$this->k,($this->h-$ty-$h*0.5)*$this->k,$this->_escape($s));
    // Advance cursor
    $this->x += $w;
    if($ln>0){ $this->x=$this->lMargin; $this->y+=$h; }
}
// draw rectangle (stroke)
function _rect($x,$y,$w,$h){
    $this->pages[$this->page].=sprintf("%.2F %.2F %.2F %.2F re S\n", $x*$this->k, ($this->h-$y-$h)*$this->k, $w*$this->k, $h*$this->k);
}
function Ln($h=null){ $this->x=$this->lMargin; $this->y+=($h===null ? $this->FontSize*1.5 : $h); }
function Output($name='', $dest='I')
{ $this->_enddoc(); header('Content-Type: application/pdf'); if($dest=='D'){ header('Content-Disposition: attachment; filename=' . ($name?:'document.pdf')); } echo $this->buffer; }

// Minimal helpers
function _getpagesize($size){ if(is_string($size)){ $s=strtolower($size); if(!isset($this->StdPageSizes[$s])) $this->Error('Unknown page size: '.$size); return $this->StdPageSizes[$s]; } if(!(is_array($size) && count($size)==2)) $this->Error('Invalid page size: '.$size); return $size; }
function _escape($s){ return str_replace(['\\','(',')',"\r"],["\\\\","\\(","\\)",''],$s); }
function Open(){ $this->state=1; }
function Error($msg){ throw new Exception('FPDF error: '.$msg); }
function _enddoc(){ if($this->state<3){ $this->_putdoc(); $this->state=3; } }
// Position helpers
function SetXY($x,$y){ $this->x=$x; $this->y=$y; }
function GetX(){ return $this->x; }
function GetY(){ return $this->y; }
// Drawing helpers
function Rect($x,$y,$w,$h){ $this->_rect($x,$y,$w,$h); }
function SetFillGray($g){ $this->pages[$this->page].=sprintf("%.3F g\n", max(0,min(1,$g))); }
function RectFill($x,$y,$w,$h,$g=0.95){ $this->SetFillGray($g); $this->pages[$this->page].=sprintf("%.2F %.2F %.2F %.2F re f\n", $x*$this->k, ($this->h-$y-$h)*$this->k, $w*$this->k, $h*$this->k); $this->SetFillGray(1); }
// Write multiple lines of text clipped within a rectangle
function TextRect($x,$y,$w,$h,$lines,$fontPt,$lineH){
    if (!is_array($lines)) $lines = [$lines];
    // define clipping path
    $this->pages[$this->page].=sprintf("q %.2F %.2F %.2F %.2F re W n\n", $x*$this->k, ($this->h-$y-$h)*$this->k, $w*$this->k, $h*$this->k);
    $ty = $y + 1.4; // top padding
    $tx = $x + 1.5; // left padding
    foreach ($lines as $idx=>$t) {
        $this->pages[$this->page].=sprintf("BT /F1 %d Tf 0 g %.2F %.2F Td (%s) Tj ET\n",
            max(6,(int)$fontPt),
            $tx*$this->k,
            ($this->h - ($ty + $idx*$lineH))*$this->k,
            $this->_escape($t)
        );
    }
    $this->pages[$this->page].="Q\n"; // end clip
}
function _putdoc(){
    $out = "%PDF-".$this->PDFVersion."\n";
    // 1: Catalog, 2: Pages, 3: Font Helvetica
    $out .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
    // Build page objects and content objects starting at object 4
    $kids = '';
    $objs = '';
    $objNum = 4;
    foreach ($this->pages as $i=>$p) {
        $w=$this->wPt; $h=$this->hPt;
        $pageNum = $objNum; $contNum = $objNum+1; $objNum += 2;
        $kids .= " $pageNum 0 R";
        $objs .= "$pageNum 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $w $h] /Resources << /Font << /F1 3 0 R >> >> /Contents $contNum 0 R >>endobj\n";
        $objs .= "$contNum 0 obj<< /Length ".strlen($p)." >>stream\n$p\nendstream endobj\n";
    }
    // Pages
    $out .= "2 0 obj<< /Type /Pages /Kids [ $kids ] /Count ".count($this->pages)." >>endobj\n";
    // Font
    $out .= "3 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";
    // Page and content objects
    $out .= $objs;
    // Xref/trailer (very naive, single section, compute current length as startxref)
    $out .= "xref\n0 $objNum\n0000000000 65535 f \n";
    $start = strlen($out);
    $out .= "trailer<< /Size $objNum /Root 1 0 R >>\nstartxref\n$start\n%%EOF";
    $this->buffer = $out;
}
}
?>
