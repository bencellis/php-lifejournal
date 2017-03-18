<?php

require_once('includes/lib.php');

$pagingparams = getPagingParams($config);

// do we want deleted records as well
$includedeleted = isset($_REQUEST['deleted']);

$journalentries = getJournalEntries($pagingparams, $includedeleted);
$lastdisplayr = '';

// edit links
$baselink = 'index.php?paging=' . $pagingparams['paging'] . '&action=';
$deletelink = $baselink . 'delete' . '&recid=';
$connectlink = $baselink . 'connectwith' . '&recid=';
$editlink = 'editEntry.php?paging=' . $pagingparams['paging'] . '&recid=';

//die('<pre>' . print_r($journalentries, true) . '</pre>');

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

	<?php if ($pagingparams['page'] == 1): ?>
	    <div class="jumbotron" style="background: url('assets/images/site-image.jpg') no-repeat right">
	      <div class="container">
	        <h1><?php echo $welcomemsg; ?></h1>
	        <div class="col-md-5">
		        <p><?php echo $byline; ?></p>
	        </div>
	      </div>
	    </div>
	<?php endif; ?>

    <div class="container">
      <div class="row">
        <div class="col-md-12">
			<p><a href="editEntry.php"><strong>Add New Entry</strong></a></p>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-12">
			<p><a href="editEntry.php"><strong>Filtering Row</strong></a></p>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-12">
			<table class="table table-condensed table-striped table-hover">
				<thead>
 					<th class="text-center">Id</th>
					<th class="text-center">Dates</th>
					<th class="text-center">Details</th>
					<th class="text-center">Source</th>
					<?php if ($includedeleted) :?>
						<th class="text-center">Del?</th>
					<?php endif; ?>
					<th width="8%" class="text-center">Actions</th>
				</thead>
				<tbody>
				<?php foreach ($journalentries as $entry): ?>
					<?php
						$entrydate = DateTime::createFromFormat('Y-m-d', $entry['realdate']);
						$recid = $entry['recid'];
					?>
					<?php if ($lastdisplayr != $entrydate->format('Y')): ?>
						<tr>
							<td class="text-center" colspan="<?php echo $includedeleted ? 6 : 5; ?>">
								<?php $lastdisplayr = $entrydate->format('Y'); ?>
								<strong><?php echo $lastdisplayr; ?></strong>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php echo $entry['recid']; ?></td>
						<td><?php echo $entry['date']; ?></td>
						<td><?php echo $entry['details']; ?></td>
						<td><?php echo $entry['source']; ?></td>
						<?php if ($includedeleted) :?>
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
    </div> <!-- /container -->
    <hr>
<?php include_once('includes/footer.php'); ?>
