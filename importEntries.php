<?php

$debugging = true;

if ($debugging) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once('includes/lib.php');

$errormsg = '';
$successmsg = '';

$pluginmanager = new pluginmanager();
$importplugins = $pluginmanager->get_plugins_for_select('import');

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

    if(!isset($_FILES['importfile']['tmp_name']) || !file_exists($_FILES['importfile']['tmp_name']) || !is_uploaded_file($_FILES['importfile']['tmp_name'])) {
        $errormsg = 'Please select a file to upload';
    }else{
        $importfile = $_FILES['importfile'];

        if ($plugin = $pluginmanager->load_plugin('import',  $_POST['importtype'])) {
            try {
                $successmsg = $plugin->process_file($importfile);
            } catch (Exception $e) {
                $errormsg = $e->getMessage();
            }
        }else{
            $errormsg = 'No such import plugin';
        }
    }
}

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>
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
   	        <div class="row">
        		<div class="col-md-12">
 	  				<h2>Import Entries</h2>
 	  			</div>
			</div>
    		<div class="row">
    			<div class="col-md-12">
					<form method="POST" name="filterForm" enctype="multipart/form-data" id="id_filterForm" class="form-inline">
    					<div class="form-group col-md-4">
    						<label for="id_importtype">Import Type</label>
    						<select name="importtype" class="form-control" id="id_importtype">
    							<?php foreach ($importplugins as $key => $value): ?>
    							<option value="<?php echo $key;?>"><?php echo $value;?></option>
    							<?php endforeach; ?>
    						</select>
    					</div>
    					<div class="form-group col-md-5">
    						<input type="file" name="importfile" id="id_importfile" />
    					</div>
    					<button name="uploadfile" type="submit" class="btn btn-success">Upload File</button>
						<button name="canceledit" type="submit" value="2" class="btn btn-default">Cancel</button>
					</form>

				</div>
			</div>
    </div> <!-- /container -->
    <hr>
<?php include_once('includes/footer.php'); ?>
