
var catList=new Array(
{foreach from=$classes item=class}
'{$class|escape:'html'}',
{/foreach}
''
);


{literal}

function onChangeImageclass()
{
	var sel=document.getElementById('imageclass');
	var idx=sel.selectedIndex;

	var isOther=idx==sel.options.length-1

	var otherblock=document.getElementById('otherblock');
	otherblock.style.display=isOther?'':'none';

}

function populateImageclass() 
{
	var sel=document.getElementById('imageclass');
	var opt=sel.options;
	var idx=sel.selectedIndex;
	var idx_value = null;
	if (idx > 0) {
		idx_value = opt[idx].value;
	}
	var first_opt = document.createElement("OPTION");
	first_opt.text = opt[0].text;
	first_opt.value = opt[0].value;
	var last_opt = document.createElement("OPTION");
	last_opt.text =opt[opt.length-1].text;
	last_opt.value =opt[opt.length-1].value;

	//clear out the options
	while (sel.options.length) {
		sel.remove(0);
	}
	//re-add the first
	opt.add(first_opt);
	

	//add the whole list
	for(i=0; i < catList.length; i++) 
	{
		if (catList[i].length)
		{
			var newoption = document.createElement("OPTION");
			opt.add(newoption);
			newoption.text = catList[i];
			newoption.value = catList[i];
			if (idx_value == catList[i])
				newoption.selected = true;
		}
	}

	//if our value is not found then use other textbox!
	if (sel.selectedIndex < 1 && idx_value != null) {
		var selother=document.getElementById('imageclassother');
		selother.value = idx_value;
		idx_value = 'Other';
	}

	//re add the other option
	opt.add(last_opt);
	if (idx_value == 'Other')
		sel.selectedIndex=opt.length-1;

	onChangeImageclass();
}


{/literal}
