if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function (obj, fromIndex) {
    if (fromIndex == null) {
        fromIndex = 0;
    } else if (fromIndex < 0) {
        fromIndex = Math.max(0, this.length + fromIndex);
    }
    for (var i = fromIndex, j = this.length; i < j; i++) {
        if (this[i] === obj)
            return i;
    }
    return -1;
  };
}

/////////////////////////////
// for puploader_inner.tpl and submit2_inner.tpl

	function pad(number, length) {
		var str = '' + number;
			while (str.length < length) {
			str = '0' + str;
		}
		return str;
	}

	function parentUpdateVariables() {
		var thatForm = window.parent.document.forms['theForm'];
		var name = thatForm.elements['selected'].value;
		var theForm = document.forms['theForm'];
		if (name != '') {
			var tags = new Array();
			var tagsEnabled = false; //need to track if any tags availabel
			for(q=0;q<theForm.elements.length;q++) {
				var ele = theForm.elements[q];
				if (thatForm.elements[ele.name+'['+name+']']) {
					//we dont need to check for select as IE does pupulate .value - doto - byt IE6 doesnt!
					if (ele.tagName.toLowerCase() == 'input' && (ele.type.toLowerCase() == 'checkbox' || ele.type.toLowerCase() == 'radio')) {
						if (ele.checked)
							thatForm.elements[ele.name+'['+name+']'].value = ele.value;
					} else {
						thatForm.elements[ele.name+'['+name+']'].value = ele.value;
					}
				} else if (ele.name.indexOf('tags[]') == 0) {
					tagsEnabled = true;
					if (ele.checked || ele.selectedIndex) //can be checkbox OR a 'select'
						tags.push(ele.value);
				} else if (ele.name == 'hfov' || ele.name == 'vfov') { //these are text entry, so need special treatment
					tagsEnabled = true;
					if (ele.value.length && parseFloat(ele.value) > 0)
						tags.push(ele.name+':'+ele.value);
				}
			}
			if (theForm.elements['imagetakenDay'] && thatForm.elements['imagetaken['+name+']']) {
				thatForm.elements['imagetaken['+name+']'].value = pad(theForm.elements['imagetakenYear'].value,4) + '-' + pad(theForm.elements['imagetakenMonth'].value,2) + '-' + pad(theForm.elements['imagetakenDay'].value,2);
			}
			if (tagsEnabled && thatForm.elements['tags['+name+']']) {
				thatForm.elements['tags['+name+']'].value = tags.join('|');
			}
		}
	}
	function updateUse6fig(ele) {
		var thatForm = window.parent.document.forms['theForm'];
		var name = thatForm.elements['selected'].value;
		
		thatForm.elements[ele.name+'['+name+']'].value = ele.checked?1:0;
	}
	
	function setupTheForm() {
		var thatForm = window.parent.document.forms['theForm'];
		var name = thatForm.elements['selected'].value;
		var theForm = document.forms['theForm'];
		
		if (thatForm.elements['tags['+name+']']) {
			var tags = thatForm.elements['tags['+name+']'].value.split('|');
		}
		for(q=0;q<theForm.elements.length;q++) {
			var ele = theForm.elements[q];
			var tagname = ele.tagName.toLowerCase()
			var type = ele.type.toLowerCase();
			if (thatForm.elements[ele.name+'['+name+']']) {
				if (tagname == 'select') {
					for(w=0;w<ele.options.length;w++)
						if (ele.options[w].value == thatForm.elements[ele.name+'['+name+']'].value)
							ele.selectedIndex = w;

					AttachEvent(ele,'change',parentUpdateVariables,false);
					
					if (ele.name == 'imageclass') {
						onChangeImageclass();
					}
					
				} else {
					if (tagname == 'input' && type == 'radio') {
						if (thatForm.elements[ele.name+'['+name+']'].value == ele.value)
							ele.checked = true;
						AttachEvent(ele,'click',parentUpdateVariables,false);
					} else if (tagname == 'input' && type == 'checkbox') {
						if (thatForm.elements[ele.name+'['+name+']'].value != '')
							ele.checked = true;
						AttachEvent(ele,'click',parentUpdateVariables,false);
					} else if (tagname == 'input' && type == 'input') {
						AttachEvent(ele,'click',parentUpdateVariables,false);
					} else {
						ele.value = thatForm.elements[ele.name+'['+name+']'].value;
						AttachEvent(ele,'mouseup',parentUpdateVariables,false);
						AttachEvent(ele,'keyup',parentUpdateVariables,false);
						AttachEvent(ele,'paste',parentUpdateVariables,false);
						AttachEvent(ele,'input',parentUpdateVariables,false);
						if (ele.disabled) {
							ele.disabled = false;
						}
					}
				}
			} else if (ele.name.indexOf('tags[]') == 0) {
				if (type == 'checkbox') {
					AttachEvent(ele,'click',parentUpdateVariables,false);
					if (tags.indexOf(ele.value) > -1) {
						ele.checked = true;
						var id = ele.id.replace(/c-/,'l-');
						if (document.getElementById(id)) {
							document.getElementById(id).style.fontWeight=ele.checked?'bold':'normal';
							document.getElementById(id).style.backgroundColor=ele.checked?'white':'';
						}
					}
				} else if (tagname == 'select') {
					AttachEvent(ele,'change',parentUpdateVariables,false);
					for(var i=1; i<ele.options.length; i++) {
						if (tags.indexOf(ele.options[i].value) > -1) {
							ele.selectedIndex = i;
						}
					}
					if (ele.selectedIndex > 0) {
						if (ele.id && ele.id == 'panoselect') {
							document.getElementById('c-pano').checked = true;
							document.getElementById('showpano').style.display = '';
							document.getElementById('hidepano').style.display = 'none';
							ele.onchange(); //to sync the fov boxes!
						}
					}
				}
			} else if (ele.name == 'hfov' || ele.name == 'vfov') {
				AttachEvent(ele,'input',parentUpdateVariables,false);
				for(var i=1; i<tags.length; i++) {
					bits = tags[i].split(':');
					if (bits.length == 2 && bits[0] == ele.name)
						ele.value = bits[1];
				}
			}
		}
		if (theForm.elements['imagetakenDay'] && thatForm.elements['imagetaken['+name+']']) {
			setdate('imagetaken',thatForm.elements['imagetaken['+name+']'].value,theForm);
			AttachEvent(theForm.elements['imagetakenDay'],'change',parentUpdateVariables,false);
			AttachEvent(theForm.elements['imagetakenMonth'],'change',parentUpdateVariables,false);
			AttachEvent(theForm.elements['imagetakenYear'],'change',parentUpdateVariables,false);
		}
		AttachEvent(ele.form,'submit',parentUpdateVariables,false);
	}
	
	
/////////////////////////////
// for puploader.tpl and submit2.tpl

function checkMultiFormSubmission() {
	var theForm = document.forms['theForm'];
	var warnings = new Array();
	var warnings_count = 0;
	var errors = new Array();
	var errors_count = 0;
	var category = false;
	var tags = false;
	
	for(q=0;q<theForm.elements.length;q++) {
		var ele=theForm.elements[q];
		
		if (ele.name.indexOf('upload_id') == 0)
			if (ele.value == '') {
				var name = "* Photo Upload (.jpg File)";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			}
		if (ele.name.indexOf('grid_reference') == 0) {
			if (ele.value == '') {
				var name = "* Subject Grid Reference";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			} else if (ele.value.length < 7) {
				var name = "* Subject Grid Reference";
				warnings[name] = (warnings[name])?(warnings[name] + 1):1;
				warnings_count = warnings_count + 1;
			}
		}
		if (ele.name.indexOf('photographer_gridref') == 0)
			if (ele.value == '') {
				var name = "* Camera Grid Reference";
				warnings[name] = (warnings[name])?(warnings[name] + 1):1;
				warnings_count = warnings_count + 1;
			}
		if (ele.name.indexOf('view_direction') == 0)
			if (ele.value == '') {
				var name = "* View Direction";
				warnings[name] = (warnings[name])?(warnings[name] + 1):1;
				warnings_count = warnings_count + 1;
			}
		if (ele.name.indexOf('title') == 0)
			if (ele.value == '') {
				var name = "* Photo Title";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			}
		if (ele.name.indexOf('tags[') == 0) {
			if (ele.value == '' && category == false) {
				var name = "* Geographical Context";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			} else {
				tags = true;
			}
		}
		if (ele.name.indexOf('imageclass[') == 0 && tags == false) {
			if (ele.value == '') {
				var name = "* Geographical Category";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			} else if (ele.value == 'Other') {
				if (theForm.elements[ele.name.replace(/imageclass/,'imageclassother')].value == '') {
					var name = "* Geographical Category Other";
					errors[name] = (errors[name])?(errors[name] + 1):1;
					errors_count = errors_count + 1;
				} else {
					category = true;
				}
			} else {
				category = true;
			}
		}
		if (ele.name.indexOf('imagetaken') == 0)
			if (ele.value == '' || ele.value == '--') {
				var name = "* Date photo taken";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			}
	}
	if (theForm.elements['pattrib']) {
		found = -1;
		for(var q=0;q <theForm.elements['pattrib'].length;q++) {
			if (theForm.elements['pattrib'][q].checked) {
				found=q;
			}
		}
		if (found >-1) {
			if (found == 1 && theForm.elements['pattrib_name'].value == '') {
				var name = "* Photographer Credit";
				errors[name] = (errors[name])?(errors[name] + 1):1;
				errors_count = errors_count + 1;
			}
		} else {
			var name = "* Attribution";
			errors[name] = (errors[name])?(errors[name] + 1):1;
			errors_count = errors_count + 1;
		}
	}

	if (errors_count > 0) {
		message = "We notice that the following fields have been left blank:\n\n";
		
		for(q in errors) {
			message = message + q + (errors[q] > 1?(" x " + errors[q] + " times"):'') +"\n";
		} 
		if (warnings_count > 0) {
			message = message + "\nAdditionally the following fields are left blank, which while not required it would be appreciated:\n\n";

			for(q in warnings) {
				message = message + q + (warnings[q] > 1?(" x " + warnings[q] + " times"):'') +"\n";
			}
		}
		message = message + "\nPlease provide the missing information\n\n";
		alert(message);
		return false;
	} else if (warnings_count > 0) {
		message = "We notice that the following fields have been left blank:\n\n";
		
		for(q in warnings) {
			message = message + q + (warnings[q] > 1?(" x " + warnings[q] + " times"):'') +"\n";
		} 
		message = message + "\nWhile you can continue without providing this information we would appreciate including as much detail as possible as it will make plotting the photo on a map much easier.\n\n";
		message = message + "Adding the missing information should be very quick by dragging the icons on the map.\n\n";
		message = message + "Click OK to add the information, or Cancel to continue anyway.";
		return !confirm(message);
	}
	return true;
	
}
