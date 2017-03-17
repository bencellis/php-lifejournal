<?php include_once('includes/header.php'); ?>

<?php include_once('includes/navigation.php'); ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron" style="background: url('assets/images/site-image.jpg') no-repeat right">
      <div class="container">
        <h1><?php echo $welcomemsg ?></h1>
        <div class="col-md-5">
	        <p>In Hindsight Dairy</p>
        </div>
      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-12">
			<p><a href="editEntry.php">Add New Entry</a></p>
        </div>
      </div>
    </div> <!-- /container -->
    <hr>
<?php include_once('includes/footer.php'); ?>
