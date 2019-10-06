<?php
/**
 * Klasa do komunikacji z drukarką fiskalną Elzab z obsługą komunikacji ElzabESC
 * 
 * UŻYWASZ NA WŁASNĄ ODPOWIEDZIALNOŚĆ!!
 * 
 * version: 0.0.1
 * autor: UNK
 **/
class pcom {
/**
 * Konstruktor klasy
 * @param string port - port na którym działa połączenie z drukarką fiskalną
 * @param string tryb - tryb w którym ma odbywać się połączenie
 **/
	private $port = '';
	private $esc;
	public $blad = "";

	public function __construct( $port, $tryb = "rb+" )
	{
		//`mode com3: baud=9600 parity=e data=8 stop=1 xon=off odsr=off octs=on dtr=hs rts=off idsr=off`;
		`mode $port baud=9600 parity=e data=8 stop=1 xon=off odsr=off octs=on dtr=hs rts=off idsr=off`;
		$this->port = $port;
		$this->esc = chr( 27 );
		/*try {
			if ( !$this->id = fopen( $port, $tryb ) )
				throw new Exception( "Nie mozna polaczyc z portem ".$port, 12 );
		} catch (Exception $e) {
			$this->blad .= $e->getMessage();
		}*/
		$this->otworzPort( $this->port, $tryb );
	}
/**
 * Otwarcie portu
 *
 * @param string - adres portu
 * @param string - tryb
 **/
	private function otworzPort( $port, $tryb )
	{
		//echo 'Otworz port '.$port.' '.$tryb."\n";
		if( isset( $this->id ) )
			fclose( $this->id );
		try {
			if ( !$this->id = fopen( $port, $tryb ) )
			//if ( !$this->id = dio_open( $port, O_RDWR ) )
				throw new Exception( "Nie mozna polaczyc z portem: ".$port, 12 );
//			else
//				stream_set_timeout( $this->id, 1 );
		} catch (Exception $e) {
			$this->blad .= $e->getMessage();
		}
	}
/**
 * Wysyła wcześniej przygotowaną serię kodów do drukarki
 *
 * @param string - seria kodów sterujących
 **/
	private function wyslij( $numery )
	{
		if ( !$this->blad )
		{
			$znaki = "";
			foreach( explode( "H", $numery ) as $Z )
			{
				if ( strlen( $Z ) > 0 )
					$znaki .= chr( hexdec( $Z ) );
			}
			if ( fwrite( $this->id, $this->esc.$znaki ) )
			//if ( dio_write( $this->id, $this->esc.$znaki ) )
				return false;
			else
				return "Nie wysłano sekwencji sterującej (Wyslij)\n";
		}
		else
		{
			echo "Wystapily bledy, nie wysylam sekwencji do drukarki (Wyslij).\n";
			return "Wystapily bledy, nie wysylam sekwencji do drukarki (Wyslij).\n";
		}
	}
/**
 * Odbiera transmisję z kasy fiskalnej
 *
 * @param int - ilość znaków do odebrania
 **/
	private function odbierztransmisje( $znakow )
	{
		if ( !$this->blad )
		{
			$napoprawnosc = 1; //normalnie 2, dio 1
			$nareszte = 0;
			$nareszte = $znakow - $napoprawnosc;

			echo "Odbieranie transmisji (".$znakow." znakow)...\n";
			$poprawnosc = ord( fgets( $this->id, $napoprawnosc ) );
			//$poprawnosc = ord( dio_read( $this->id, $napoprawnosc ) );
			echo 'Poprawnosc: '.$poprawnosc."\n";
			if ( $poprawnosc == 21 ) {
				echo "Odrzucono sekwencje sterujaca.\n";
				return false;
			} elseif( $poprawnosc == 6 ) {
				if ( $nareszte > 0 )
				{
					$wartosc = fgets( $this->id, $nareszte );
					//$wartosc = dio_read( $this->id, $nareszte );
					return $wartosc;
				}
				else
					return "Sukces";
			} else {
				sleep(5);
				echo "Wystapily inne bledy, nie odbieram transmisji (Odbierz transmisje).\n";
				return false;
			}
		}
		else {
			echo "Wystapily bledy, nie odbieram transmisji (Odbierz transmisje).\n";
			return false;
		}
	}
	public function bledy()
	{
		return $this->blad;
	}
	public function odbierzdate()
	{
		if ( !$this->wyslij( "40H" ) ) //35H skrócona data, 40H dłuższa data (sekundy)
		{
			echo "wyslij 40H\n";
			$znakow = 13;//15 - dla fopen, 13 - dla dio
			$dane = $this->odbierztransmisje( $znakow );
			echo "odbierztransmisje\n";
			$ciag = "";
			foreach( str_split( $dane ) as $znak )
			{
				$ciag .= ord( $znak );
			}
			return preg_replace( "@([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})@", "$1-$2-$3 $4:$5:$6", $ciag );
		}
		else
			return "Wystapily bledy, nie nawiazuje komunikacji (DAH).";
	}
	public function sprawdzstatus( $format = 'tekst' ) //tekst, binarny
	{
		$doznakow = 8;
		$status = array(
			0 => array(
				0 => "Wpisane dane o producencie",
				1 => "Wpisane dane o uzytkowniku",
				2 => "Modul w trybie tylko odczyt",
				3 => "Modul w trybie fiskalnym",
				4 => "wersja oprogramowania",
				5 => "wersja oprogramowania",
				6 => "wersja oprogramowania",
				7 => "wersja oprogramowania",
			),
			1 => array(
				0 => "brak wolnego miejsca w bazie kontrolnej nazw i stawek",
				1 => "w pamieci znajduje sie dokument do wydrukowania",
				2 => "w pamieci fiskalnej zostalo mniej niz 30 rekordow do zapisania",
				3 => "nie zostal wykonany raport dobowy za poprzedni dzien sprzedazy",
				4 => "blad w pamieci CMOS",
				5 => "nastapilo zablokowanie nazwy towaru w paragonie",
				6 => "brak wyswietlacza klienta",
				7 => "brak komunikacji z kontrolerem drukarki",
			),
			2 => array(
				0 => "w buforze drukowania sa znaki do wydrukowania",
				1 => "brak papieru",
				2 => "awaria drukarki",
				3 => "za niskie napiecie akulumatora, dalsza praca mozliwa ale powiadom serwis",
				4 => "nastapilo uniewaznienie paragonu",
				5 => "w pamieci paragonu zostalo mniej niz 1kB miejsca",
				6 => "wydruk dokumentu zatrzymany z powodu braku papieru",
				7 => "brak komunikacji z kontrolerem drukarki",
			),
			3 => array(
				0 => "bit zawsze rowny 0",
				1 => "bit zawsze rowny 0",
				2 => "bit zawsze rowny 1",
				3 => "szuflada zamknieta lub niepodlaczona",
				4 => "bit zawsze rowny 1",
				5 => "bit zawsze rowny 1",
				6 => "zwora serwisowa w pozycji serwisowej",
				7 => "brak komunikacji z kontrolerem drukarki",
			),
			4 => array(
				0 => "-",
				1 => "-",
				2 => "-",
				3 => "-",
				4 => "-",
				5 => "-",
				6 => "w buforze wycen lekow znajduja sie dane",
				7 => "bufor wycen lekow zapelniony",
			),
			5 => array(
				0 => "drukarka pracuje wylacznie na zasilaniu akumulatorowym",
				1 => "paragon jest w trakcie wystawiania",
				2 => "bit zawsze równy 0",
				3 => "bit zawsze równy 0",
				4 => "bit zawsze równy 0",
				5 => "bit zawsze równy 0",
				6 => "bit zawsze równy 0",
				7 => "bit zawsze równy 0",
			)
		);
		if ( !$this->wyslij( "5BH" ) )
		{
			$wynik = "";
			$this->blad = "";
			$dane = $this->odbierztransmisje( 2 );
			$ciag[0] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "54H" );
			$dane = $this->odbierztransmisje( 2 );
			$ciag[1] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "55H" );
			$dane = $this->odbierztransmisje( 2 );
			$ciag[2] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "56H" );
			$dane = $this->odbierztransmisje( 2 );
			$ciag[3] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "5DH" );
			$dane = $this->odbierztransmisje( 2 );
			$ciag[4] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "5FH" );
			$dane = $this->odbierztransmisje( 2 );
			$ciag[5] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );
			if ( $format == 'binarny' )
			{
				foreach( $ciag as $k => $bity )
				{
					foreach( $bity as $c )
						$wynik .= $c;
					if ( $k < 5 ) $wynik .= "\n";
				}
			}
			elseif ( $format == 'tekst' )
			{
				foreach( $ciag as $k => $bity )
				{
					foreach( $bity as $kbit => $c )
					{
						$kbit = abs( $kbit - 7 );
						if ( $c )
						{
							if ( $status[$k][$kbit] != '-' && !preg_match( "@(bit zawsze rowny [0-1]|wersja oprogramowania)@", $status[$k][$kbit] ) )
							{
								switch( $k )
								{
									case 0:
										switch( $kbit )
										{ //0-2;
											case 0:
											break;
											case 1:
											break;
											case 2:
												$this->blad .= $status[$k][$kbit];
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
											break;
											case 7:
											break;
										}
									break;
									case 1:
										switch( $kbit )
										{ //1-1;1-7;
											case 0:
											break;
											case 1:
												$this->blad .= $status[$k][$kbit];
											break;
											case 2:
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
											break;
											case 7:
												$this->blad .= $status[$k][$kbit];
											break;
										}
									break;
									case 2:
										switch( $kbit )
										{ //2-1;2-2;2-6;2-7;
											case 0:
											break;
											case 1:
												$this->blad .= $status[$k][$kbit];
											break;
											case 2:
												$this->blad .= $status[$k][$kbit];
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
												$this->blad .= $status[$k][$kbit];
											break;
											case 7:
												$this->blad .= $status[$k][$kbit];
											break;
										}
									break;
									case 3:
										switch( $kbit )
										{ //3-7;
											case 0:
											break;
											case 1:
											break;
											case 2:
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
											break;
											case 7:
												$this->blad .= $status[$k][$kbit];
											break;
										}
									break;
									case 4:
										switch( $kbit )
										{
											case 0:
											break;
											case 1:
											break;
											case 2:
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
											break;
											case 7:
											break;
										}
									break;
									case 5:
										switch( $kbit )
										{ //5-1
											case 0:
											break;
											case 1:
												$this->blad .= $status[$k][$kbit];
											break;
											case 2:
											break;
											case 3:
											break;
											case 4:
											break;
											case 5:
											break;
											case 6:
											break;
											case 7:
											break;
										}
									break;
								}
								$wynik .= $status[$k][$kbit]."\n";
							}
						}
					}
				}
			}

			return $wynik;
		}
		else
			return "Wystapily bledy, nie nawiazuje komunikacji.";
	}
	public function sprawdzsprzedaz()
	{
		$doznakow = 8;
		echo "wyslij DAH\n";
		echo $this->blad;
		if ( !$this->wyslij( "DAH" ) )
		{
			$dane = $this->odbierztransmisje( 36 );
			$ptu['a'] = str_split( substr( $dane,  0, 5 ) );
			$ptu['b'] = str_split( substr( $dane,  5, 5 ) );
			$ptu['c'] = str_split( substr( $dane, 10, 5 ) );
			$ptu['d'] = str_split( substr( $dane, 15, 5 ) );
			$ptu['e'] = str_split( substr( $dane, 20, 5 ) );
			$ptu['f'] = str_split( substr( $dane, 25, 5 ) );
			$ptu['g'] = str_split( substr( $dane, 30, 5 ) );
			$ptua = "";
			foreach( $ptu as $typ => $stopa )
			{
				foreach( $stopa as $k => $A )
				{
					$ptu[$typ][$k] = str_split( str_pad( decbin( ord( $A ) ), $doznakow, "0", STR_PAD_LEFT ) );
					$dzialanie[$typ] = 0;
				}
			}
			foreach( $ptu as $typ => $stopa )
			{
				foreach( $stopa as $miejsce => $stopy )
				{
					foreach( $stopy as $miejsce2 => $bit )
					{
						$y = ( abs( $miejsce2 - 7 ) ) + ( 7 * $miejsce ) + $miejsce;
						$dzialanie[$typ] += $bit * pow( 2, $y );
					}
				}
				$dzialanie[$typ] = substr( $dzialanie[$typ], 0, -2 ).".".substr( $dzialanie[$typ], -2 );
			}
			return $dzialanie;
		}
		else
			return array( 'blad' => true, 'tekst' => "Wystapily bledy, nie nawiazuje komunikacji." );
	}
	public function drukujdobowy()
	{
		if ( !$this->wyslij( "25H" ) )
		{
			$dane = $this->odbierztransmisje( 2 );
			if ( $dane == "Sukces" )
				return "Poprawnie wydrukowano raport dobowy.";
			else
				return $dane;
		}
		else
			return "Wystapily bledy, nie nawiazuje komunikacji.";
	}
	public function drukujokresowy( $rodzaj, $zakres, $czyskrocony = false )
	{
		if ( !$this->wyslij( "31H" ) )
		{
			$dane = $this->odbierztransmisje( 2 );
			if ( $dane == "Sukces" )
			{
				$Wrodzaj = $Wzakres = "";
				switch( $rodzaj )
				{
					case "wgdat":
						$Wrodzaj = 0;
						$Wzakres = "01H01H00H03H00H01H01H01H00H03H03H02H";
					break;
					case "wgdat":
/*
Lnrpocz,Hnrpocz,Lnrkon,Hnrkon
Lnrpocz – młodszy bajt początkowego numeru raportu;
Hnrpocz – starszy bajt początkowego numeru raportu;
Lnrkon – młodszy bajt końcowego numeru raportu;
Hnrkon – starszy bajt końcowego numeru raportu;
*/
						$Wrodzaj = 1;
						$Wzakres = "";
					break;
					case "miesieczny":
						$Wrodzaj = 2;
						$Wzakres = "01H01H00H03H";
					break;
				}
				if ( $czyskrocony == true )
					$Wrodzaj += 3;
				//$this->wyslij( "0".$Wrodzaj."H" );
				//$this->wyslij( "0".$Wrodzaj."H".$Wzakres );
				//$dane = $this->odbierztransmisje( 2 );
/*

*/
				return "w:".$Wrodzaj." || ".$dane;
			}
		}
		else
			return "Wystapily bledy, nie nawiazuje komunikacji.";
	}
	public function nrostfiskalnego()
	{
		$this->wyslij( "66H" );
		$dane = ord( $this->odbierztransmisje( 3 ) );
		return $dane;
	}
	public function nrostdobowego()
	{
		$this->wyslij( "D9H" );
		$dane = ord( $this->odbierztransmisje( 3 ) );
		return $dane;
	}
	public function wyczysclistebledow()
	{
		$this->blad = "";
	}
	public function otwarcieszuflady()
	{
		if ( !$this->wyslij( "5BH" ) )
		{
			$wynik = "";
			$this->blad = "";
//			$dane = $this->odbierztransmisje( 4 );
//			$ciag[0] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );

			$this->wyslij( "57H" );
//			$dane = $this->odbierztransmisje( 4 );
//			$ciag[1] = str_split( str_pad( decbin( ord( $dane ) ), $doznakow, "0", STR_PAD_LEFT ) );
		}
	}
}
