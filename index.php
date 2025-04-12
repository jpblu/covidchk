<!doctype html>
<html>
<head>

	<?
		include('lib/config.php');
		include('lib/sstat.php');
		include('version.php');
	?>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="COVID_19 CSV Data Loader">
	<meta name="author" content="Andrea Fusco">

	<title>COVID-19 CSV Data Loader</title>

	<!-- Bootstrap core CSS -->
	<link href="css/bootstrap.min.css?v=462" rel="stylesheet">
	<link href="css/jquery-ui.min.css?v=1140" rel="stylesheet">

	<!-- Font-Awesome Icons -->
	<link href="css/all.min.css?v=660" rel="stylesheet">

	<!-- JQuery Core -->
	<script src="js/jquery-3.7.1.min.js"></script>
	<script src="js/jquery-ui.min.js?v=1140"></script>

	<!-- Bootstrap core JavaScript -->
	<script src="js/bootstrap.min.js?v=462"></script>

	<!-- JQuery Plugins -->
	<script src="js/jquery.validate.min.js?v=1195"></script>
	<script src="js/additional-methods.min.js?v=1195"></script>

	<!-- Custom styles for this template -->
	<link href="css/offcanvas.css" rel="stylesheet">
	<script src="js/offcanvas.js"></script>
	<script type="module" src="js/chart.umd.js?v=444"></script>
	<script src="js/cv19monitor.js?v=120"></script>

</head>

<body class="bg-light">

	<nav class="navbar navbar-expand-md fixed-top navbar-dark bg-dark">

		<div class="collapse navbar-collapse" id="navbarsExampleDefault">

			<?
				if (isset($_GET['startdate']) && isset($_GET['enddate'])) {
					$startdate = $_GET['startdate'];
					$enddate = $_GET['enddate'];
				} else {
					$startdate = date("Y-m-d", strtotime(date("Y-m-d", strtotime( date("Y-m-d")))."-1 month"));
					$enddate = date('Y-m-d');
				}
			?>

			<a class="navbar-brand" href="#">Covid-19 CSV Data Loader</a>
			<input type="text" class="form-control col-1 my-2 my-sm-0 mr-2 ml-auto" id="startdate" value="<?=$startdate; ?>">
			<input type="text" class="form-control col-1 my-2 my-sm-0 mr-2" id="enddate" value="<?=$enddate; ?>">
			<button class="btn btn-outline-success my-2 mr-2 my-sm-0" type="button" id="page_refresh" title="Aggiorna"><i class="fas fa-sync"></i></button>
			<a class="btn btn-primary my-2 my-sm-0" href="lists.php">Statistiche Incrementi Regioni/Province</a>

		</div>

	</nav>

	<main role="main" class="container">

		<div class="row mt-3 mb-3">

			<!-- Dati Provincia -->
			<?	if (isset($_GET['prov'])) { $codprov = $_GET['prov']; } else { $codprov = '034'; }
			  $provdata = SStats::getCV19Table($codprov,$startdate,$enddate);
				$regdata = SStats::getCV19PercTmp($provdata[0]['codice_regione']);
				$regvacc = SStats::getCV19Vacc($provdata[0]['codice_regione']);
			?>

			<div class="col-md-7">

			    <div class="card">

			        <div class="card-header d-flex">

        				<select class="form-control form-control-sm" id="codprov" style="width: 200px;">
        					<option value="999">Seleziona la Provincia</option>
        					<? 	foreach(GlobalConfig::$province as $key => $value) {
        							if (isset($_GET['prov']) && $_GET['prov'] == $key) { $selected = 'selected'; } else if (!isset($_GET['prov']) && $key == '034') { $selected = 'selected'; } else { $selected = ''; }
        							echo "<option value='".$key."' ".$selected.">".$value."</option>";
        						}
        					?>
        				</select>

        				<div class="ml-auto"><b>Totale Positivi: <? echo number_format($provdata[0]['totale_casi'],0,'.','.'); ?></b></div>

			        </div>

					<div class="card-body">

						<canvas class="my-3" id="XDaysChart"></canvas>

						<div class="dropdown-divider my-3"></div>

						<canvas class="my-3" id="XIncrementChart"></canvas>

						<div class="dropdown-divider my-3"></div>

						<canvas class="my-3" id="XIncrement3DayChart"></canvas>

					</div>

				</div>

			</div>

			<div class="col-md-5">

				<div class="card mb-3">
					<div class="card-header">
						Totale Vaccinazioni<br>
						(Dato Aggregato Regione <b><?=GlobalConfig::$regioni[$provdata[0]['codice_regione']]; ?></b>)
					</div>
					<div class="card-body">

						<table class="table">
							<tr>
								<th>Ultimo Aggiornamento</th>
								<td><?=$regvacc['ultimo_aggiornamento']; ?></td>
							</tr>
							<tr>
								<th>Totale Vaccinazioni</th>
								<td><? echo number_format($regvacc['dosi_somministrate'],0,'.','.'); ?></td>
							</tr>
							<tr>
								<th>Totale Dosi Consegnate</th>
								<td><? echo number_format($regvacc['dosi_consegnate'],0,'.','.'); ?></td>
							</tr>
							<tr>
								<th>% Somministrazione</th>
								<td><?=$regvacc['percentuale_somministrazione']; ?> %</td>
							</tr>

						</table>

					</div>
				</div>

				<div class="card mb-3">
					<div class="card-header">

						Incremento Giornaliero su <b><?=GlobalConfig::$province[$codprov]; ?></b>
					</div>
					<div class="card-body" style="height: 412px; overflow-x: auto;">

						<table class="table small text-center">
							<tr>
								<th>Data</th>
								<th>Incremento</th>
								<th>Media su 3 giorni</th>
							</tr>

						<? 	foreach($provdata as $key => $value) {	?>
							<tr>
								<td><?=$provdata[$key]['data']; ?></td>
								<td>+ <?=$provdata[$key]['incremento']; ?></td>
								<td>+ <?=$provdata[$key]['dayincr']; ?></td>
							</tr>
						<?	}	?>

						</table>

					</div>
				</div>

				<div class="card">
					<div class="card-header">

						Percentuale Positivi su Tamponi Giornalieri<br>
						(Dato Aggregato Regione <b><?=GlobalConfig::$regioni[$provdata[0]['codice_regione']]; ?></b>)
					</div>
					<div class="card-body" style="height: 412px; overflow-x: auto;">

						<table class="table">
							<tr>
								<th>Data</th>
								<th>Tamponi</th>
								<th>Nuovi Pos.</th>
								<th>% Positivi</th>
							</tr>

						<? 	foreach($regdata as $key => $value) {	?>
							<tr class="small text-center">
								<td><?=$regdata[$key]['data']; ?></td>
								<td><?=$regdata[$key]['tamponi_day']; ?></td>
								<td>+ <?=$regdata[$key]['nuovi_positivi']; ?></td>
								<td><?=$regdata[$key]['perc_new']; ?> %</td>
							</tr>
						<?	}	?>

						</table>

					</div>
				</div>

			</div>

		</div>

	</main>

	<footer class="w-100 text-center my-3">Dati ufficiali forniti dal <a href="https://github.com/pcm-dpc/COVID-19">Dipartimento della Protezione Civile</a> &copy; 2020-<?= date('Y'); ?> AndreaFusco.net - covidChk v<?=$ver; ?> <br><small>Aggiornamento Giornaliero alle 18:40</small></footer>

	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-3022323-6"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-3022323-6');
	</script>


</body>
</html>
