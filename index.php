<?php

$debugging = true;

if ($debugging) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once('includes/lib.php');
$version = getVersion();

$errormsg = '';
$successmsg = '';
$searchterm = '';

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
				$successmsg = 'Record has been successfully connected';
			}
			break;
	}
}

//$today = new DateTime();
//$years = range($today->format("Y"), $config['startyear']);
$years = getAllEntryYears();			// for filter
$months = range(1,12);

$ishome = (empty($_REQUEST));						// if we coming to start again ????
$pagingparams = getPagingParams($config);			// this get paging, ordering and filtering data

//die(print_r($pagingparams, true));

$isfiltered = (empty($pagingparams['filteryear']) || !empty($pagingparams['filtersource']));
$issearching = !empty($pagingparams['searchterm']);

//die('<pre>' . print_r($pagingparams, true) . '</pre>');
list($otday, $otmonth) = explode('-', date('d-m'));
//die('<pre>' . $otday . ' -> ' . $otmonth . '</pre>');
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'onthisday')) {
    if (($otday = $_REQUEST['thisday']) && ($otmonth = $_REQUEST['thismonth'])) {
        //die('<pre>' . $otday . ' -> ' . $otmonth . '</pre>');
        $journalentries = getOnThisDay((int) $otday, (int) $otmonth);
    } else {
        die("Invalid Parameters");
    }
} else {
    $journalentries = getJournalEntries($pagingparams);
}

$journalsources = getJournalSourcetypes();

$lastdisplayr = '';

// edit links
$paginglink = 'index.php';
$baselink =  $paginglink . '?action=';
$deletelink = $baselink . 'delete' . '&recid=';
$connectlink = $baselink . 'connectwith' . '&recid=';
$onthisdaylink = $baselink . 'onthisday' . '&thisday=' . $otday . '&thismonth=' . $otmonth; 

$newlink = 'editEntry.php';
$importlink = 'importEntries.php';
$editlink = $newlink . '?recid=';

$undated = 'Undated';

//die('<pre>' . print_r($journalentries, true) . '</pre>');


?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

	<!-- Modal -->
	<div class="modal fade" id="viewEntry" tabindex="-1" role="dialog" aria-labelledby="viewEntryLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="viewEntryLabel">View Entry</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div id="fullentrytext" class="modal-body">
					This is where the text should go.
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

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
 	  <!-- search box -->
		<div class="row">
			<div class="col-md-12">
				<div class="well well-lg text-right">
					<form method="POST" action="<?php echo $paginglink; ?>" name="searchForm" id="id_searchForm" class="form-inline">
						<a href="<?php echo $onthisdaylink; ?>"><button type="button" class="btn btn-info">On This Day</button></a>
						<div class="form-group">
							<label for="id_searchterm">Search:</label>
							<input type="text" name="searchterm" class="form-control" id="id_searchterm" placeholder="Search..." value="<?php echo isset($pagingparams['searchterm']) ? $pagingparams['searchterm'] : '' ; ?>"/>
						</div>
							<button type="submit" name="dosearch" class="btn btn-success">Find</button>&nbsp;
							<button type="submit" name="clrsearch" class="btn btn-default">Clear Search</button>
					</form>
				</div>
			</div>
		</div>
      <!-- Filtering Row -->
		<div class="row">
			<div class="col-md-12">
				<div class="well well-lg">
            		<div class="row">
            			<div class="col-md-12">
							<h4>Filter records</h4>
						</div>
            		</div>
            		<div class="row">
            			<div class="col-md-12">
        					<form method="POST" action="<?php echo $paginglink; ?>" name="filterForm" id="id_filterForm" class="form-inline">
        						<div class="checkbox col-md-1" >
        							<label>
        								<strong>Include Deleted?&nbsp;</strong>
        								<input name="includedeleted" value="1" type="checkbox" <?php echo isset($pagingparams['includedeleted']) ? "checked" : ''; ?>/>
        							</label>
        						</div>
        						<div class="form-group col-md-3">
        							<label for="id_filtersource">By Source Type</label>
        							<select name="filtersource" class="form-control" id="id_filtersource">
        								<option value="all">All</option>
        								<?php
        									$selectedsource = isset($pagingparams['filtersource']) ? $pagingparams['filtersource'] : '';
        									foreach ($journalsources as $source) {
        										if ($source) {			// miss zero
        											echo "<option " . (($selectedsource == $source) ? 'selected' : '') . " value='$source'>$source</option>\n";
        										}
        									}
        								?>
        							</select>
        						</div>
        						<div class="form-group col-md-3">
        							<label for="id_filteryear">By Year</label>
        							<select name="filteryear" class="form-control" id="id_filteryear">
        								<option value="all">All</option>
        								<?php
        									$selectedyear = isset($pagingparams['filteryear']) ? $pagingparams['filteryear'] : '';
        									echo "<option " . (($selectedyear === 0) ? 'selected' : '') . " value='0'>$undated</option>\n";
        									foreach ($years as $year) {
        										if ($year) {			// miss zero
        											echo "<option " . (($selectedyear == $year) ? 'selected' : '') . " value='$year'>$year</option>\n";
        										}
        									}
        								?>
        							</select>
        						</div>
        						<div class="form-group col-md-3">
        							<label for="id_filtermonth">And Month</label>
        							<select name="filtermonth" class="form-control" id="id_filtermonth" <?php echo (isset($pagingparams['filteryear'])) ? '': 'disabled' ?>>
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
        			<br/>
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
						<th width="12%" class="text-center">Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
							<a href="<?php echo $newlink; ?>"><strong>Add New Entry</strong></a> |
							<a href="<?php echo $importlink; ?>"><strong>Import Records</strong></a>
						</td>
					</tr>
				<?php if (!empty($journalentries)) :?>
    				<?php foreach ($journalentries as $entry): ?>
    
    					<?php
    					if ($entry['realdate'] == "0000-00-00" || $entry['realdate'] === null) {
    							$entry['date'] = $undated;
    						}else{
    							$entrydate = DateTime::createFromFormat('Y-m-d', $entry['realdate']);
    						}
    						$recid = $entry['recid'];
    					?>
    					<?php if (!$isfiltered): ?>
    						<?php if ($entry['date'] != $undated && $lastdisplayr != $entrydate->format('Y')): ?>
    							<tr>
    								<td class="text-center" colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
    									<?php $lastdisplayr = $entrydate->format('Y'); ?>
    									<strong><?php echo $lastdisplayr; ?></strong>
    								</td>
    							</tr>
    						<?php elseif ($entry['date'] == $undated && ($lastdisplayr != $undated)): ?>
    							<tr>
    								<td class="text-center" colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
    									<?php $lastdisplayr = $undated; ?>
    									<strong><?php echo $lastdisplayr; ?></strong>
    								</td>
    							</tr>
    						<?php endif; ?>
    					<?php endif; ?>
    					<tr>
    						<td><?php echo $entry['recid']; ?></td>
    						<td><?php echo $entry['date']; ?></td>
    						<td class="entrydetails">
    							<?php echo str_replace("\n", '<br />', $entry['details']); ?>
    							<?php if (substr(trim($entry['details']), strlen(trim($entry['details'])) - 3) == '...') :?>
        							&nbsp;
        							<span data-id="<?php echo $entry['recid']; ?>" class="glyphicon glyphicon-eye-open viewentry"></span>
        						<?php endif; ?>
    						</td>
    						<td><?php echo $entry['source']; ?></td>
    						<?php if (isset($pagingparams['includedeleted'])) :?>
    							<td><?php echo $entry['deleted']; ?></td>
    						<?php endif; ?>
    						<td style="vertical-align: middle" class="text-center">
    							<!-- <a href="#" id="id_viewrecord" class="editLink editEntry" title="View">
    								<span class="glyphicon glyphicon-eye-open"></span>
    							</a>&nbsp;-->
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
				<?php else: ?>
					<tr>
						<td colspan="<?php echo isset($pagingparams['includedeleted']) ? 6 : 5; ?>">
							<p>No Records have been found.</p>
						</td>
					</tr>					
				<?php endif; ?>
				</tbody>
			</table>
        </div>
      </div>
 	  <?php if (true): ?>
	      <div class="row">
	        <div class="col-md-12">
				<div>
					<hr />
					<?php
					if (!empty($journalentries)) {
							echo getPagingBar($paginglink, $pagingparams);
						}
					?>
					<hr />
				</div>
	        </div>
	      </div>
 	  <?php endif?>
    </div> <!-- /container -->
    <hr>
<?php include_once('includes/footer.php'); ?>
