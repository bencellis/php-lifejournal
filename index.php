<?php

require_once('includes/lib.php');

$errormsg = '';
$successmsg = '';

if (isset($_REQUEST['action']) && isset($_REQUEST['recid'])) {
	switch ($_REQUEST['action']) {
		case 'delete' :
			if (!$errormsg = markEntryAsDeleted($_REQUEST['recid'])) {
				$successmsg = 'Record has been successfully deleted';
			}
			break;
		case 'connectwith' :
			if (isset($_REQUEST['connectid'])) {
				if (!$errormsg = connectJournalEntries($_REQUEST['recid'], $_REQUEST['connectid'])) {
					$successmsg = 'Record has been successfully connected';
				}
			}else{
				$errormsg = "A Connected Id must be specified";
			}
			break;
	}
}

$today = new DateTime();
$years = range($today->format("Y"), $config['startyear']);
$years = getAllEntryYears();
$months = range(1,12);

$ishome = (empty($_REQUEST));			// if we coming to start again ????
$pagingparams = getPagingParams($config);			// this get paging, ordering and filtering data

//die('<pre>' . print_r($pagingparams, true) . '</pre>');

$journalentries = getJournalEntries($pagingparams);

$lastdisplayr = '';


// edit links
$paginglink = 'index.php';
$baselink =  $paginglink . '?action=';
$deletelink = $baselink . 'delete' . '&recid=';
$connectlink = $baselink . 'connectwith' . '&recid=';

$newlink = 'editEntry.php';
$editlink = $newlink . '?recid=';

//die('<pre>' . print_r($journalentries, true) . '</pre>');


?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

	<?php if ($ishome): ?>
	    <div class="jumbotron" style="background: url('assets/images/site-image.jpg') no-repeat right">
	      <div class="container">
	        <h1><?php echo $welcomemsg; ?></h1>
	        <div class="col-md-5">
		        <p><?php echo $byline; ?></p>
	        </div>
	      </div>
	    </div>
	<?php endif; ?>
	<br/>
    <div class="container">
 	  <?php if ($errormsg): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div class="alert alert-danger" role="alert"><?php echo $errormsg; ?></div>
	        </div>
	      </div>
 	  <?php endif?>
 	  <?php if ($successmsg): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div class="alert alert-success" role="alert"><?php echo $successmsg; ?></div>
	        </div>
	      </div>
 	  <?php endif?>
      <!-- Filtering Row -->
		<div class="row">
			<div class="col-md-12">
				<div class="well well-lg">
					<form method="POST" action="<?php echo $paginglink; ?>" name="filterForm" id="id_filterForm" class="form-inline">
						<div class="form-group col-md-2">
							<h4>Filter records</h4>
						</div>
						<div class="checkbox col-md-2" >
							<label>
								<strong>Include Deleted?&nbsp;</strong>
								<input name="includedeleted" value="1" type="checkbox" <?php echo isset($pagingparams['includedeleted']) ? "checked" : ''; ?>/>
							</label>
						</div>
						<div class="form-group col-md-3">
							<label for="id_filteryear">By Year</label>
							<select name="filteryear" class="form-control" id="id_filteryear">
								<option value="all">All</option>
								<?php
									$selectedyear = isset($pagingparams['filteryear']) ? $pagingparams['filteryear'] : '';
									echo "<option " . (($selectedyear === 0) ? 'selected' : '') . " value='0'>Undated</option>\n";
									foreach ($years as $year) {
										echo "<option " . (($selectedyear == $year) ? 'selected' : '') . " value='$year'>$year</option>\n";
									}
								?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="id_filtermonth">And Month</label>
							<select name="filtermonth" class="form-control" id="id_filtermonth" <?php echo (isset($pagingparams['filtermonth'])) ? '': 'disabled' ?>>
								<option value="all">All</option>
								<?php
									$selectedmonth = isset($pagingparams['filtermonth']) ? $pagingparams['filtermonth'] : '';
									foreach ($months as $month) {
										echo "<option " . (($selectedmonth == $month) ? 'selected' : '') . " value='$month'>". sprintf("%02d", $month) . "</option>\n";
									}
								?>
							</select>
						</div>
						<button name="filterby" type="submit" class="btn btn-success">Filter</button>
						<button name="clearfilter" type="submit" class="btn btn-default" <?php echo count($pagingparams) ? '' : 'disabled'; ?>>Clear Filter</button>
					</form>
				</div>
			</div>
		</div>
      <!-- end Filtering Row -->
      <div class="row">
        <div class="col-md-12">
			<table class="table table-condensed table-striped table-hover">
				<thead>
					<tr>
	 					<th class="text-center">Id</th>
						<th class="text-center">Dates</th>
						<th class="text-center">Details</th>
						<th class="text-center">Source</th>
						<?php if (isset($pagingparams['includedeleted'])) :?>
							<th class="text-center">Del?</th>
						<?php endif; ?>
						<th width="8%" class="text-center">Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
							<a href="<?php echo $newlink; ?>"><strong>Add New Entry</strong></a>
						</td>
					</tr>
				<?php foreach ($journalentries as $entry): ?>
					<?php
						$entrydate = DateTime::createFromFormat('Y-m-d', $entry['realdate']);
						$recid = $entry['recid'];
					?>
					<?php if ($lastdisplayr != $entrydate->format('Y')): ?>
						<tr>
							<td class="text-center" colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
								<?php $lastdisplayr = $entrydate->format('Y'); ?>
								<strong><?php echo $lastdisplayr; ?></strong>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php echo $entry['recid']; ?></td>
						<td><?php echo $entry['date']; ?></td>
						<td><?php echo str_replace("\n", '<br />', $entry['details']); ?></td>
						<td><?php echo $entry['source']; ?></td>
						<?php if (isset($pagingparams['includedeleted'])) :?>
							<td><?php echo $entry['deleted']; ?></td>
						<?php endif; ?>
						<td style="vertical-align: middle" class="text-center">
							<a href="<?php echo $editlink . $recid; ?>" class="editLink editEntry" title="Edit">
								<span class="glyphicon glyphicon-wrench"></span>
							</a>&nbsp;
							<a href="<?php echo $deletelink . $recid; ?>" class="editLink delEntry" title="Delete">
								<span class="glyphicon glyphicon-remove"></span>
							</a>&nbsp;
							<a href="<?php echo $connectlink . $recid; ?>" class="editLink linkEntry" title="Connect">
								<span class="glyphicon glyphicon-link"></span>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
        </div>
      </div>
 	  <?php if (true): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div>
					<hr />
					<?php echo getPagingBar($paginglink, $pagingparams); ?>
					<hr />
				</div>
	        </div>
	      </div>
 	  <?php endif?>
    </div> <!-- /container -->
    <hr>
<?php include_once('includes/footer.php'); ?>
