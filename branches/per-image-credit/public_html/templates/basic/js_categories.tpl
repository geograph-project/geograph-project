var catList=new Array(
{foreach from=$classes item=class}
'{$class|escape:'html'}',
{/foreach}
''
);