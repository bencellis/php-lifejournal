/*
 * Custom JS Code
 */

$(function() {
	
	// on page unload check for checked checkboxes
	updateFollowingFlds($('#id_allYear'));
	updateFollowingFlds($('#id_allMonth')); 
	updateFollowingFlds($('#id_allDay')); 
	updateFollowingFlds($('#id_startYear'));
	
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

