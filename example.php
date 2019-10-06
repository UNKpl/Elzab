<?php
require_once("com.class.php");

$wymusdobowy = false;
$data = date("Y-m-d H:i:s");
echo "Data z systemu: ".$data."\n";
$com3 = new pcom( "com3:" );


$ptu = $com3->sprawdzsprzedaz();
if ( isset( $ptu['blad'] ) && $ptu['blad'] ) {
	echo $ptu['tekst']."\n";
	exit( 1 );
}
echo "spr sprz\n";
$data = $com3->odbierzdate();
echo "odb date\n";
echo 'Data: '.$data."\n";

if ( $data != 0 )
{
	echo "Data z drukarki: ".$data."\n\n----------------------";
	if ( strlen( $data ) >= 17 )
		list( $rok, $miesiac, $dzien, $godzina, $minuta, $sekunda ) = preg_split( "/[- :]/", $data );
		
	echo "\n\n";

	echo "Bledy polaczenia:\t".$com3->blad."\n";
	$com3->sprawdzstatus( );
	echo "----------------- przed drukiem ----------------\n";
	echo "Bledy stanu drukarki:\t".$com3->blad."\n";
	$dokonanychsprzedazy = $com3->nrostfiskalnego();
	$nrostdobowegoprzeddrukiem = $com3->nrostdobowego();
	$js = new stdClass;
	$js->wydrukowanodobowy = false;
	if ( $ptu['a'] > 0 && ( $godzina >= 19 || $wymusdobowy ) )
	{
		$com3->drukujdobowy();
		$nrostdobowego = $com3->nrostdobowego();
		if ( $nrostdobowegoprzeddrukiem != $nrostdobowego )
		{
			$js->wydrukowanodobowy = true;
			echo "\n------------------------------------------------\nDrukowanie raportu numer: ".( $nrostdobowego )."\nWystawionych paragonow ".$dokonanychsprzedazy." na kwote ".number_format( $ptu['a'], 2, ',', ' ' )." zl\n------------------------------------------------\n\n";
		}
		else
			echo "\n------------------------------------------------\nMogly wystawpic bledy przy drukowaniu raportu\n------------------------------------------------\n\n";
		$js->nrostdobowego = $nrostdobowego;
	}
	$js->nrostdobowegoprzeddrukiem = $nrostdobowegoprzeddrukiem;
	$js->dokonanychsprzedazy = $dokonanychsprzedazy;
	$js->kwota = $ptu['a'];
	$js->bledy = $com3->blad;
	$js->data = $data;
	echo "Data (yyyy-mm-dd hh:mm:ss):\t".( !$com3->blad ? substr( date( "Y" ), 0, 2 ) : "" ).$data."\n";
	echo "Sprzedaz:\t".number_format( $ptu['a'], 2, ',', ' ' )." zl\n";
	sleep( 5 );
	$com3->sprawdzstatus( );
	$ptu = $com3->sprawdzsprzedaz();
	$data = $com3->odbierzdate();
	echo "------------------- po druku -------------------\n";
	echo "Bledy stanu drukarki:\t".$com3->blad."\n";
	echo "Data (yyyy-mm-dd hh:mm:ss):\t".( !$com3->blad ? substr( date( "Y" ), 0, 2 ) : "" ).$data."\n";
	echo "Sprzedaz:\t".number_format( $ptu['a'], 2, ',', ' ' )." zl\n";
	if ( !$com3->blad ) 
	{
	}
}
else
	echo "Nie pobrano daty, nie przechodze dalej\n";
