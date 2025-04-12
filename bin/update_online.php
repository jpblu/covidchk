<?php
/**
 * Covid-19 CSV Data Loader
 * Copyright © 2020, Andrea Fusco
 *
 * Licensed under Creative Commons By-Nc-Nd
 * Redistributions of files must retain the above copyright notice.
 *
 * @Copyright     	Copyright © 2020, Andrea Fusco
 * @License       	Creative Commons By-Nc-Nd (http://creativecommons.org/licenses/by-nc-nd/3.0/)
 * @File		  			update_oniline.php
 * @Description	  	Refresh Data DB from PcM-DpC (https://github.com/pcm-dpc/COVID-19)
 * @Version		  		1.2.2
 * @Created		  		2020-03-20
 * @Updated		  		2025-04-12
 */

require("../lib/config.php");
require("../lib/logs.php");

if (isset($_POST['token']) && $_POST['token'] == GlobalConfig::$auth_token) {

	//Effettuo Upload del file
	$logFilePath = __DIR__ . '/../logs/cronLog.log';
	echo "Carico i file...";
	flush();
	unlink('../file/covid19_province.csv');
	unlink('../file/covid19_regioni.csv');
	unlink('../file/covid19_vaccini.csv');

	$url = 'https://github.com/pcm-dpc/COVID-19/raw/master/dati-province/dpc-covid19-ita-province.csv';
	$img_province = '../file/covid19_province.csv';
	file_put_contents($img_province, file_get_contents($url));

	$url = 'https://github.com/pcm-dpc/COVID-19/raw/master/dati-regioni/dpc-covid19-ita-regioni.csv';
	$img_regioni = '../file/covid19_regioni.csv';
	file_put_contents($img_regioni, file_get_contents($url));

	$url = 'https://github.com/italia/covid19-opendata-vaccini/raw/master/dati/vaccini-summary-latest.csv';
	$img_vaccini = '../file/covid19_vaccini.csv';
	file_put_contents($img_vaccini, file_get_contents($url));

	echo " OK!<br>";
	flush();

	//Cancello la tabella covid19_province
	echo "Svuoto le tabelle...";
	flush();
	$mysqli = new mysqli(GlobalConfig::$dbconnect['host'], GlobalConfig::$dbconnect['user'], GlobalConfig::$dbconnect['pswd'], GlobalConfig::$dbconnect['name']);

	if ($mysqli->connect_errno) {
		echo "Errore in connessione al DB: ".$mysqli->connect_errno;
		exit();
	}

	$mysqli->query("TRUNCATE TABLE covid19_province");
	$mysqli->query("TRUNCATE TABLE covid19_regioni;");
	$mysqli->query("TRUNCATE TABLE covid19_vaccini;");

	echo "OK!<br>";
	flush();

	//Ricarico la tabella covid19_province, covid19_regioni e covid19_vaccini
	echo "Carico i nuovi dati...";
	flush();

	try {

		#Province
		$fp = fopen($img_province, 'r');

		if (!$fp) {
				die('Errore nell\'apertura del file CSV.');
		}

		// Rimuove eventuale BOM (Byte Order Mark)
		if (fgets($fp, 4) !== "\xef\xbb\xbf") {
				rewind($fp);
		}

		// Leggi l'intestazione
		$header = fgetcsv($fp);

		// Verifica che l'intestazione sia valida
		if ($header === false) {
				die('Errore nella lettura dell\'intestazione del CSV.');
		}

		// Esegui il ciclo sulle righe del CSV
		while (($row = fgetcsv($fp)) !== false) {
			// Salta le righe vuote o anomale
			if (count($row) === 1 && $row[0] === null) {
				continue;
			}

			// Verifica che il numero di colonne sia uguale all'intestazione
			if (count($header) !== count($row)) {
					// Salta la riga o gestiscila in qualche altro modo (log degli errori, ecc.)
					echo "Riga con numero di colonne errato: " . implode(",", $row) . "<br>";
					continue;
			}

			// Combina l'intestazione con la riga per ottenere un array associativo
			$csvRow = array_combine($header, $row);

			// Sanifica i dati per l'inserimento nel database
			$data = substr($mysqli->real_escape_string($csvRow['data']), 0, 10);
			$stato = $mysqli->real_escape_string($csvRow['stato']);
			$codice_regione = $mysqli->real_escape_string($csvRow['codice_regione']);
			$denominazione_regione = $mysqli->real_escape_string($csvRow['denominazione_regione']);
			$codice_provincia = $mysqli->real_escape_string($csvRow['codice_provincia']);
			$denominazione_provincia = htmlentities($mysqli->real_escape_string($csvRow['denominazione_provincia']));
			$sigla_provincia = $mysqli->real_escape_string($csvRow['sigla_provincia']);
			$lat = $mysqli->real_escape_string($csvRow['lat']);
			$lng = $mysqli->real_escape_string($csvRow['long']);
			$totale_casi = $mysqli->real_escape_string($csvRow['totale_casi']);

			// Inserisci la riga nel database
			$query = "INSERT INTO covid19_province (data, stato, codice_regione, denominazione_regione, codice_provincia, denominazione_provincia, sigla_provincia, lat, lng, totale_casi)
								VALUES ('$data', '$stato', '$codice_regione', '$denominazione_regione', '$codice_provincia', '$denominazione_provincia', '$sigla_provincia', '$lat', '$lng', '$totale_casi')";

			if (!$mysqli->query($query)) {
					echo "Errore nell'inserimento dei dati: " . $mysqli->error;
					exit();
			}
		}

		// Chiudi il file
		fclose($fp);

		#Regioni
		$fp_regioni = fopen($img_regioni, 'r');

		if (!$fp_regioni) {
				die('Errore nell\'apertura del file CSV delle regioni.');
		}

		// Rimuove eventuale BOM (Byte Order Mark)
		if (fgets($fp_regioni, 4) !== "\xef\xbb\xbf") {
				rewind($fp_regioni);
		}

		// Leggi l'intestazione
		$header_regioni = fgetcsv($fp_regioni);

		// Verifica che l'intestazione sia valida
		if ($header_regioni === false) {
				die('Errore nella lettura dell\'intestazione del CSV delle regioni.');
		}

		// Esegui il ciclo sulle righe del CSV
		while (($row = fgetcsv($fp_regioni)) !== false) {
			// Salta le righe vuote o anomale
			if (count($row) === 1 && $row[0] === null) {
				continue;
			}

			// Verifica che il numero di colonne sia uguale all'intestazione
			if (count($header_regioni) !== count($row)) {
					// Salta la riga o gestiscila in qualche altro modo (log degli errori, ecc.)
					echo "Riga con numero di colonne errato: " . implode(",", $row) . "<br>";
					continue;
			}

			// Combina l'intestazione con la riga per ottenere un array associativo
			$csvRow = array_combine($header_regioni, $row);

			// Sanifica i dati per l'inserimento nel database
			$data = substr($mysqli->real_escape_string($csvRow['data']), 0, 10);
			$stato = $mysqli->real_escape_string($csvRow['stato']);
			$codice_regione = $mysqli->real_escape_string($csvRow['codice_regione']);
			$denominazione_regione = htmlentities($mysqli->real_escape_string($csvRow['denominazione_regione']));
			$lat = $mysqli->real_escape_string($csvRow['lat']);
			$lng = $mysqli->real_escape_string($csvRow['long']);
			$ricoverati_con_sintomi = $mysqli->real_escape_string($csvRow['ricoverati_con_sintomi']);
			$terapia_intensiva = $mysqli->real_escape_string($csvRow['terapia_intensiva']);
			$totale_ospedalizzati = $mysqli->real_escape_string($csvRow['totale_ospedalizzati']);
			$isolamento_domiciliare = $mysqli->real_escape_string($csvRow['isolamento_domiciliare']);
			$totale_positivi = $mysqli->real_escape_string($csvRow['totale_positivi']);
			$variazione_totale_positivi = $mysqli->real_escape_string($csvRow['variazione_totale_positivi']);
			$nuovi_positivi = $mysqli->real_escape_string($csvRow['nuovi_positivi']);
			$dimessi_guariti = $mysqli->real_escape_string($csvRow['dimessi_guariti']);
			$deceduti = $mysqli->real_escape_string($csvRow['deceduti']);
			$casi_da_sospetto_diagnostico = $mysqli->real_escape_string($csvRow['casi_da_sospetto_diagnostico']);
			$casi_da_screening = $mysqli->real_escape_string($csvRow['casi_da_screening']);
			$totale_casi = $mysqli->real_escape_string($csvRow['totale_casi']);
			$tamponi = $mysqli->real_escape_string($csvRow['tamponi']);
			$casi_testati = $mysqli->real_escape_string($csvRow['casi_testati']);
			$note = htmlentities($mysqli->real_escape_string($csvRow['note']));

			// Inserisci la riga nel database
			$query = "INSERT INTO covid19_regioni (data, stato, codice_regione, denominazione_regione, lat, lng, ricoverati_con_sintomi, terapia_intensiva, totale_ospedalizzati, isolamento_domiciliare, totale_positivi, variazione_totale_positivi, nuovi_positivi, dimessi_guariti, deceduti, casi_da_sospetto_diagnostico, casi_da_screening, totale_casi, tamponi, casi_testati, note)
								VALUES ('$data', '$stato', '$codice_regione', '$denominazione_regione', '$lat', '$lng', '$ricoverati_con_sintomi', '$terapia_intensiva', '$totale_ospedalizzati', '$isolamento_domiciliare', '$totale_positivi', '$variazione_totale_positivi', '$nuovi_positivi', '$dimessi_guariti', '$deceduti', '$casi_da_sospetto_diagnostico', '$casi_da_screening', '$totale_casi', '$tamponi', '$casi_testati', '$note')";

			if (!$mysqli->query($query)) {
					echo "Errore nell'inserimento dei dati delle regioni: " . $mysqli->error;
					exit();
			}
		}

		// Chiudi il file
		fclose($fp_regioni);

		#Vaccini

		$fp_vaccini = fopen($img_vaccini, 'r');

		if (!$fp_vaccini) {
				die('Errore nell\'apertura del file CSV dei vaccini.');
		}

		// Rimuove eventuale BOM (Byte Order Mark)
		if (fgets($fp_vaccini, 4) !== "\xef\xbb\xbf") {
				rewind($fp_vaccini);
		}

		// Leggi l'intestazione
		$header_vaccini = fgetcsv($fp_vaccini);

		// Verifica che l'intestazione sia valida
		if ($header_vaccini === false) {
				die('Errore nella lettura dell\'intestazione del CSV dei vaccini.');
		}

		// Esegui il ciclo sulle righe del CSV
		while (($row = fgetcsv($fp_vaccini)) !== false) {
			// Salta le righe vuote o anomale
			if (count($row) === 1 && $row[0] === null) {
				continue;
			}

			// Verifica che il numero di colonne sia uguale all'intestazione
			if (count($header_vaccini) !== count($row)) {
					// Salta la riga o gestiscila in qualche altro modo (log degli errori, ecc.)
					echo "Riga con numero di colonne errato: " . implode(",", $row) . "<br>";
					continue;
			}

			// Combina l'intestazione con la riga per ottenere un array associativo
			$csvRow = array_combine($header_vaccini, $row);

			// Sanifica i dati per l'inserimento nel database
			$area = $mysqli->real_escape_string($csvRow['area']);
			$dosi_somministrate = $mysqli->real_escape_string($csvRow['dosi_somministrate']);
			$dosi_consegnate = $mysqli->real_escape_string($csvRow['dosi_consegnate']);
			$percentuale_somministrazione = htmlentities($mysqli->real_escape_string($csvRow['percentuale_somministrazione']));
			$ultimo_aggiornamento = $mysqli->real_escape_string($csvRow['ultimo_aggiornamento']);
			$codice_NUTS1 = $mysqli->real_escape_string($csvRow['N1']);
			$codice_NUTS2 = $mysqli->real_escape_string($csvRow['N2']);
			$codice_regione_ISTAT = $mysqli->real_escape_string($csvRow['ISTAT']);
			$nome_area = htmlentities($mysqli->real_escape_string($csvRow['reg']));

			// Inserisci la riga nel database
			$query = "INSERT INTO covid19_vaccini (area, dosi_somministrate, dosi_consegnate, percentuale_somministrazione, ultimo_aggiornamento, codice_NUTS1, codice_NUTS2, codice_regione_ISTAT, nome_area)
								VALUES ('$area', '$dosi_somministrate', '$dosi_consegnate', '$percentuale_somministrazione', '$ultimo_aggiornamento', '$codice_NUTS1', '$codice_NUTS2', '$codice_regione_ISTAT', '$nome_area')";

			if (!$mysqli->query($query)) {
					echo "Errore nell'inserimento dei dati dei vaccini: " . $mysqli->error;
					exit();
			}
		}

		// Chiudi il file
		fclose($fp_vaccini);

		echo "OK!<br>";
		flush();

		//Formattazione Date
		echo "Formattazione campi data...    ";
		flush();

		$mysqli->query("UPDATE covid19_province SET DATA = SUBSTRING(DATA,1,10);");
		$mysqli->query("UPDATE covid19_regioni SET DATA = SUBSTRING(DATA,1,10);");

		echo "OK!<br>";

		$logtxt = "CSV Update OK";
		Logs::writeBatchLog($logFilePath, $logtxt);

	} catch (Exception $e) {
		echo "errore: ".$e->getMessage();
		Logs::writeBatchLog($logFilePath, "ERROR: ".$e->getMessage());
		exit;
	}
} else {
	header("HTTP/1.1 403 Forbidden");
}

?>