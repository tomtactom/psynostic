<?php
ini_set('max_execution_time',60);
//Suche nach Updates
$getVersions = file_get_contents('https://update.lupusgui.de/current-release-versions.php') or die ('ERROR');
$current_version = $options['version'];
if ($getVersions != '') {
	$your_version = 'Aktuelle Version: v'.$current_version;
	$search_update = 'Suche nach neuen Updates...';
	$versionList = explode("\n", $getVersions);	
	foreach ($versionList as $aV)
	{
		if ( $aV > $current_version) {
			$new_update = 'Neues Update: v'.$aV;
			$found = true;
			
			//Lade Datei herunter, Wenn sie noch nicht vorhanden sind
			if ( !is_file(  $_SERVER['DOCUMENT_ROOT'].'lupusgui-'.$aV.'.zip' )) {
				$download_update = 'Läd Update herunter...';
				$newUpdate = file_get_contents('https://update.lupusgui.de/update/lupusgui-'.$aV.'.zip');
				$dlHandler = fopen($_SERVER['DOCUMENT_ROOT'].'lupusgui-'.$aV.'.zip', 'w');
				if ( !fwrite($dlHandler, $newUpdate) ) {
					$error = 'Update konnte nicht gespeichert werden. Vorgang abgebrochen'; 
					exit(); 
				}
				fclose($dlHandler);
				$success_msg = 'Update wurde erfolgreich installiert';
			} else {
				$msg = 'Update wurde bereits heruntergeladen';
			}
			
			if (isset($_POST['doUpdate'])) {
				//Öffne die Datei und extrahiere sie
				$zipHandle = zip_open($_SERVER['DOCUMENT_ROOT'].'lupusgui-'.$aV.'.zip');
				
				while ($aF = zip_read($zipHandle) ) {
					$thisFileName = zip_entry_name($aF);
					$thisFileDir = dirname($thisFileName);
					
					//Fahre fort, wenn es keine Datei ist
					if ( substr($thisFileName,-1,1) == '/') continue;
					
					//Erstelle die benötigten Ordner
					if ( !is_dir ( $_SERVER['DOCUMENT_ROOT'].'/'.$thisFileDir ) ) {
						 mkdir ( $_SERVER['DOCUMENT_ROOT'].'/'.$thisFileDir );
					}
					
					//Überschreibe die Dateien
					if ( !is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$thisFileName) ) {
						$contents = zip_entry_read($aF, zip_entry_filesize($aF));
						$contents = str_replace("\r\n", "\n", $contents);
						$updateThis = '';
							$updateThis = fopen($_SERVER['DOCUMENT_ROOT'].'/'.$thisFileName, 'w');
							fwrite($updateThis, $contents);
							fclose($updateThis);
							unset($contents);
							$success_msg = 'Update wurde erfolgreich installiert';
					}
				}
				$updated = TRUE;
			}
			else {
				$new_update_found = true;
			}
			break;
		} else {
			$no_update = 'Es wurde kein Update gefunden.';
		}
	}
	if ($updated == true) {
		unlink($_SERVER['DOCUMENT_ROOT'].'lupusgui-'.$aV.'.zip');
			$stmt = $db->prepare("UPDATE `option` SET `option_value` = ?, `updated_at` = CURRENT_TIMESTAMP() WHERE `option_name` = ?");
			$stmt->bind_param("ss", $value, $name);
			$name = 'version';
			$value = $aV;
			$stmt->execute();
			$stmt->close();
		
		$success_msg =  '&raquo; Wurde zu Version '.$aV.' upgedatet.';
	} elseif ($found != true) {
			$msg = '&raquo; Kein Update verfügbar.';
	}
}
?>
