$tekst = [string]$args[0]
$znakow = [int]$args[1]

function wyslij($tekst) {
	try {
		$ciag = '';
		foreach( $bajt in $tekst.Split(" ") ) {
			$ciag += [char][int]$bajt
		}
		$port.Write($ciag)
	} catch {
		$port.Close()
		Write-Host "PowerShell Script Error"
		Write-Host $_.Exception.Message
		Write-Host $_.Exception.ItemName
		Break
	}
}

function odbierz($znakow) {
	try {
		$data = $port.ReadByte()
		$string = ""
		if ( $data -eq 6 ) {
			for ( $i=0; $i -lt $znakow; $i++ ) {
				$string += [string]$port.ReadByte()
			}
		} elseif ( $data -eq 21 ) {
			$string = "Wystapily bledy"
		} else {
			$string = "Wystapily inne bledy"
		}
		return $string
	} catch {
		$port.Close()
		Write-Host "PowerShell Script Error"
		Write-Host $_.Exception.Message
		Write-Host $_.Exception.ItemName
		Break
	}
}

#BytesToRead
#CtsHolding
#DrsHolding
#DtrEnable
#IsOpen
#RtsEnable
#mode $port baud=9600 parity=e data=8 stop=1 xon=off odsr=off octs=on dtr=hs rts=off idsr=off

$port = new-Object System.IO.Ports.SerialPort
$port.PortName = "COM3"
$port.BaudRate = "9600"
$port.Parity = "Even"
$port.DataBits = 8
$port.StopBits = 1
$port.ReadTimeout = 2000
$port.DtrEnable = 1
$port.RtsEnable = 0
$port.open()

if ( $port.IsOpen ) {
	wyslij $tekst
	$string = odbierz $znakow
	Write-Host $string
	$port.close()
}
