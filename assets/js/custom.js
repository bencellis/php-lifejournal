/*
 * 
 * Custom JS Code
 */

$(function() {
	
	$('.delEntry').click(function(e){
		return confirm('Are you sure you want to do this?');
	});
	
	$('.linkEntry').click(function(e){
		if (input = prompt('Enter Id of record you want to link this record to')) {
			// add recid to link
			if (Number.isInteger(Number(input))) {
				// redirect to ulr plus 
				var connectedid = '&connectid=' + input;
				document.location = this.href + connectedid;
			}else{
				alert('Only Ids (numbers) are allowed here.');
				return false;
			}
		}else{
			return false;
		}
	});
	
	$('.viewentry').click(function(e){
		var id = $(this).data('id');
		$.ajax({
		    type:'get',
		    url:'getentrydetails.php',
		    data: {
				"id" : id
			},
		    dataType: "json",
		    success: function(response) {
				$('#fullentrytext').html(response);
		    },
		    error: function(response) {
				$('#fullentrytext').html("Error get details for record " + id);
		    }
		});
		$("#viewEntry").modal("show");
	});
	
	$('#id_filteryear').change(function(e){
		if ( $(this).val() == 'all' || $(this).val() == 0 ) {
			$('#id_filtermonth').val('all');
			$('#id_filtermonth').prop('disabled', true);
		}else{
			$('#id_filtermonth').prop('disabled', false);
		}
	});
	
	// TODO check we on right page for this
	// on page unload check for checked checkboxes
	if ($('#id_editForm').length > 0) {
		if ($('#id_startYear').val() == 0) {
			updateFollowingFlds($('#id_startYear'));
		}else{
			$('.form-allfield').each(function(){
				if ($(this).prop('checked')) {
					updateFollowingFlds($(this));
					return false;
				}
			});
		}
	}
	
	// event functions
	$('#id_startYear').change(function(){
		updateFollowingFlds($(this)); 
	});
	
	$('.form-allfield').click(function(e){
		updateFollowingFlds($(this)); 
	});	
	
	/*
	 * function to disable the following start Date Elements
	 */
	function updateFollowingFlds(target) {
		var changefromhere = false;
		var targetid = target.prop('id');
		
		var disable = false;		
		if (targetid == 'id_startYear') {
			disable = target.val() == 0;
		}else{
			disable = target.prop('checked');
		}

		$('.form-startflds').each(function(){
			var thisid = $(this).prop('id');
			if (!changefromhere) {
				if (thisid == targetid) {
					changefromhere = true;
				}
			}else{
				$(this).prop('disabled', disable);
			}
		});
		// update all the end date fields
		$('.form-endflds').prop('disabled', disable);
	}

	/*
	 * Function to validate date - http://www.jquerybyexample.net/2011/12/validate-date-using-jquery.html
	 */
	function isDate(txtDate){
	  var currVal = txtDate;
	  if(currVal == '')
	    return false;
	  
	  //Declare Regex  
	  var rxDatePattern = /^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/; 
	  var dtArray = currVal.match(rxDatePattern); // is format OK?

	  if (dtArray == null)
	     return false;
	 
	  //Checks for mm/dd/yyyy format.
	  dtMonth = dtArray[1];
	  dtDay= dtArray[3];
	  dtYear = dtArray[5];

	  if (dtMonth < 1 || dtMonth > 12)
	      return false;
	  else if (dtDay < 1 || dtDay> 31)
	      return false;
	  else if ((dtMonth==4 || dtMonth==6 || dtMonth==9 || dtMonth==11) && dtDay ==31)
	      return false;
	  else if (dtMonth == 2)
	  {
	     var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
	     if (dtDay> 29 || (dtDay ==29 && !isleap))
	          return false;
	  }
	  return true;
	}	
});

