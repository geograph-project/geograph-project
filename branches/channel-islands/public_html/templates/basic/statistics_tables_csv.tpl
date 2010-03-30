{foreach from=$tables key=tableindex item=table}{if $tableindex == $whichtable}
{foreach from=$table.table.0 key=name item=value}{$name|escape:"csv"},{/foreach}

{foreach from=$table.table item=row}
{foreach from=$row key=name item=value}{$value|escape:"csv"},{/foreach}

{/foreach}
{/if}{/foreach}