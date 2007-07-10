{foreach from=$table.0 key=name item=value}{$name},{/foreach}

{foreach from=$table item=row}
{foreach from=$row key=name item=value}{$value},{/foreach}

{/foreach}

