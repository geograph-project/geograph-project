<html>
<head>
<title>Locale Test...</title>
</head>
<body>
<h1>Locale Test...</h1>
<p>
<?php

#setlocale(LC_ALL,'C'); //to match online servers...
#setlocale(LC_CTYPE,'de_DE');
#setlocale(LC_TIME,'de_DE');
setlocale(LC_NUMERIC,'de_DE');
#setlocale(LC_COLLATE,'C');

$a=1.0;
$b=1;
$c=1.2;
$d=1.2E20;

$s="<br />";

echo $a.$s;
echo $b.$s;
echo $c.$s;
echo $d.$s;

echo "$a<br />";
echo "$b<br />";
echo "$c<br />";
echo "$d<br />";

$sd=(string)($d);
$svd=strval($d);
print $d;
print $s;
echo $d;
echo $s;
printf("%s%s%s%s",$sd,$s,$svd,$s);
printf("%s%s",$d,$s);

setlocale(LC_NUMERIC,'C');

echo $a.$s;
echo $b.$s;
echo $c.$s;
echo $d.$s;


echo "$a<br />";
echo "$b<br />";
echo "$c<br />";
echo "$d<br />";
?>
</p>
</body>
</html>
