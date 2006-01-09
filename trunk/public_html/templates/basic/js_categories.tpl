var catList=new Array();
{foreach from=$classes item=class}
catList[catList.length]='{$class|escape:'html'}';
{/foreach}
