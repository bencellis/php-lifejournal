<?php
require_once('includes/lib.php');

$today = new DateTime();
$years = range($today->format("Y"), $config['startyear']);
$months = range(1,12);
$days = range(1,31);
$hours = range(0,23);
$minutes = range(0,59);
$errormessage = '';
$successmessage = '';

$record = array();

if (!empty($_POST)) {
	if (isset($_POST['canceledit'])) {
		//redirect back to home page
		$redirecturl = 'index.php';
		if (isset($_REQUEST['paging'])) {		// only if we have paging stuff to remember
			$pagingstuff = getPagingParams($config);
			$redirecturl .= '?paging=' . $pagingstuff['paging'];
		}
		RedirectTo($redirecturl);
	}

	if ($errormessage = ProcessPostData($_POST)) {
		//TODO restore the values submitted for correction
		$record = getSubmittedRecord($_POST);
	}else{
		$successmessage = 'Record has been saved - click cancel to return to main screen.';
		// want to remember last entered date
		$lastdate = new DateTime();
		if ($lastyear = $_POST['startYear']) {
			$lastmonth = empty($_POST['startMonth']) ? 1 : $_POST['startMonth'];
			$lastday = empty($_POST['startMonth']) ? 1 : $_POST['startMonth'];
			$lastdate->setDate($lastyear, $lastmonth, $lastday);
		}
		// get a new record
		$record = getEmptyRecord($lastdate);
	}
}else if (!empty($_GET['recid'])) {
	//this is a get record and we have several options
	if (!$record = getJounalEntry($_GET['recid'])) {
		$errormessage = 'Record Not found';
		$record = getEmptyRecord($today);
	}
}else{
	// we are dealing with an new record
	$record = getEmptyRecord($today);
}


// we need to ensure we have date objects to work with
$startdate = null;
if ($record['startdate']) {
	$startdate = DateTime::createFromFormat('Y-m-d', $record['startdate']);
}
$enddate = null;
if ($record['enddate']) {
	$enddate = DateTime::createFromFormat('Y-m-d', $record['enddate']);
}
$starttime = null;
if ($record['starttime']) {
	$starttime = new DateTime($record['starttime']);
}
$endtime = null;
if ($record['endtime']) {
	$endtime = new DateTime($record['endtime']);
}

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-12">
			<h1>Edit Journal Entry</h1>
        </div>
      </div>
 	  <?php if ($errormessage): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div class="alert alert-danger" role="alert"><?php echo $errormessage; ?></div>
	        </div>
	      </div>
 	  <?php endif?>
 	  <?php if ($successmessage): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div class="alert alert-success" role="alert"><?php echo $successmessage; ?></div>
	        </div>
	      </div>
 	  <?php endif?>
      <div class="row">
        <div class="col-md-4">
			<p>Record Id: <?php echo $record['recid'] == 0 ? 'New' : $record['recid']; ?></p>
        </div>
        <div class="col-md-4">
			<p><?php echo $record['deleted'] ? 'Deleted Record': 'Active Record';  ?></p>
        </div>
        <div class="col-md-4 text-right">
			<p>Record Source: <?php echo $record['sourcetype']; ?></p>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
        	<hr />
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
			<!-- form -->
			<form name="editForm" id="id_editForm" method="POST">
				<!-- hidden fields -->
				<div class="row">
					<div class="col-md-12">
						<input type="hidden" name="recid" value="<?php echo $record['recid']; ?>" />
						<input type="hidden" name="deleted" value="<?php echo $record['deleted']; ?>" />
						<input type="hidden" name="sourcetype" value="<?php echo $record['sourcetype']; ?>" />
						<input type="hidden" name="paging" value="<?php echo isset($_REQUEST['paging']) ? $_REQUEST['paging'] : ''; ?>" />
					</div>
				</div>
				<!--  start dates -->
				<div class="row">
					<div class="col-md-1">
						<strong>Start Date</strong>
					</div>
					<div class="col-md-2">
						<label for="id_startYear">Start Year</label>
						<select name="startYear" class="form-control form-startflds" id="id_startYear">
							<?php
								$selectedyear = $startdate ? $startdate->format('Y') : 0;
								echo "<option " . (($selectedyear == 0) ? 'selected' : '') . " value='0'>Undated</option>\n";
								foreach ($years as $year) {
									echo "<option " . (($selectedyear == $year) ? 'selected' : '') . " value='$year'>$year</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						<label for="id_allYear">All Year</label>
						<input class="form-control form-startflds form-allfield" type="checkbox" <?php echo ($record['allyear']) ? "checked" : ''; ?> name="allYear" id ="id_allYear" />
					</div>
					<div class="col-md-2">
						<label for="id_startMonth">Start Month</label>
						<select name="startMonth" class="form-control form-startflds" id="id_startMonth">
							<?php
								$selectedmonth = $startdate ? $startdate->format('m') : 0;
								foreach ($months as $month) {
									echo "<option " . (($selectedmonth == $month) ? 'selected' : '') . " value='$month'>". sprintf("%02d", $month) . "</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						<label for="id_allMonth">All Month</label>
						<input class="form-control form-startflds form-allfield" type="checkbox" <?php echo ($record['allmonth']) ? "checked" : ''; ?> name="allMonth" id ="id_allMonth" />
					</div>
					<div class="col-md-2">
						<label for="id_startDay">Start Day</label>
						<select name="startDay" class="form-control form-startflds" id="id_startDay">
							<?php
								$selectedday = $startdate ? $startdate->format('d'): 0;
								foreach ($days as $day) {
									echo "<option " . (($selectedday == $day) ? 'selected' : '') . " value='$day'>". sprintf("%02d", $day) ."</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						<label for="id_allDay">All Day</label>
						<input class="form-control form-startflds form-allfield" type="checkbox" <?php echo ($record['allday']) ? "checked" : ''; ?> name="allDay" id ="id_allDay" />
					</div>
					<div class="col-md-1">
						<label for="id_startHour">Time</label>
						<select name="startHour" class="form-control form-startflds" id="id_startHour">
							<?php
								$selectedhour = $starttime ? $starttime->format('G') : 0;
								foreach ($hours as $hour) {
									echo "<option " . (($selectedhour == $hour) ? 'selected' : '') . " value='$hour'>". sprintf("%02d", $hour) . "</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						<label for="id_startMinute">&nbsp</label>
						<select name="startMinute" class="form-control form-startflds" id="id_startMinute">
							<?php
								$selectedminute = $starttime ? (int) $starttime->format('i') : 0;
								foreach ($minutes as $minute) {
									echo "<option " . (($selectedminute == $minute) ? 'selected' : '') . " value='$minute'>". sprintf("%02d", $minute) . "</option>\n";
								}
							?>
						</select>
					</div>
				</div>
				<!--  end dates -->
				<div class="row">
					<div class="col-md-1">
						<strong>End Date</strong>
					</div>
					<div class="col-md-1">
						<label for="id_isevent">Same</label>
						<input class="form-control form-startflds form-allfield" type="checkbox" <?php echo ($record['isevent']) ? "checked" : ''; ?> name="isevent" id ="id_isevent" />
					</div>
					<div class="col-md-2">
						<label for="id_endYear">End Year</label>
						<select name="endYear" class="form-control form-endflds" id="id_endYear">
							<?php
							$selectedyear = $enddate ? $enddate->format('Y') : 0;
							foreach ($years as $year) {
								echo "<option " . (($selectedyear == $year) ? 'selected' : '') . " value='$year'>$year</option>\n";
							}
							?>
						</select>
					</div>
					<div class="col-md-2">
						<label for="id_endMonth">End Month</label>
						<select name="endMonth" class="form-control form-endflds" id="id_endMonth">
							<?php
								$selectedmonth = $enddate ? $enddate->format('m') : 0;
								foreach ($months as $month) {
									echo "<option " . (($selectedmonth == $month) ? 'selected' : '') . " value='$month'>". sprintf("%02d", $month) . "</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						&nbsp;
					</div>
					<div class="col-md-2">
						<label for="id_endDay">End Day</label>
						<select name="endDay" class="form-control form-endflds" id="id_endDay">
							<?php
								$selectedday = $enddate ? $enddate->format('d') : 0;
								foreach ($days as $day) {
									echo "<option " . (($selectedday == $day) ? 'selected' : '') . " value='$day'>". sprintf("%02d", $day) ."</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						&nbsp;
					</div>
					<div class="col-md-1">
						<label for="id_endHour">Time</label>
						<select name="endHour" class="form-control form-endflds" id="id_endHour">
							<?php
								$selectedhour = $endtime ? $endtime->format('G') : 0;
								foreach ($hours as $hour) {
									echo "<option " . (($selectedhour == $hour) ? 'selected' : '') . " value='$hour'>". sprintf("%02d", $hour) . "</option>\n";
								}
							?>
						</select>
					</div>
					<div class="col-md-1">
						<label for="id_endMinute">&nbsp</label>
							<select name="endMinute" class="form-control form-endflds" id="id_endMinute">
							<?php
								$selectedminute = $endtime ? (int) $endtime->format('i') : 0;
								foreach ($minutes as $minute) {
									echo "<option " . (($selectedminute == $minute) ? 'selected' : '') . " value='$minute'>". sprintf("%02d", $minute) . "</option>\n";
								}
							?>
						</select>
					</div>
				</div>
				<!-- details -->
      			<div class="row" style="padding: 6px 12px;">
        			<div class="col-md-2 text-center">
						<strong>Details</strong>
					</div>
        			<div class="col-md-10">
						<textarea name="details" id="id_details" class="form-control" rows="6"><?php echo $record['details']?></textarea>
					</div>
				</div>
			  <div class="form-group">
			    <div class="col-sm-offset-2 col-sm-10">
			      <button name="saverecord" type="submit" value="0" class="btn btn-success">Save Record</button>&nbsp;
			      <?php if ($record['recid'] > 0 && !$record['deleted']): ?>
			      	<button name="deleterecord" type="submit" value="1" class="btn btn-warning">Delete Record</button>&nbsp;
			      <?php endif; ?>
			      <button name="canceledit" type="submit" value="2" class="btn btn-default">Cancel</button>
			    </div>
			  </div>
	        </form>
	     </div>
      </div>
    </div> <!-- /container -->
    <hr>

<?php include_once('includes/footer.php'); ?>