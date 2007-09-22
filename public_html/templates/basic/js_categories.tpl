var {$varname}=new Array(
{foreach from=$classes item=class}
'{$class|escape:'html'}',
{/foreach}
''
);