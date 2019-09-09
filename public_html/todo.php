<title>Geograph Toy Todo</title>

<h3>Geograph Toy Todo</h3>

<style>
body { font-family:verdana; }
table td { padding:3px; }
table tr { background-color:pink; }
table tr.done {	background-color:lightgreen; }
table tr.notice { background-color:cream; }
table td:nth-child(1) {  }
table td:nth-child(2) {	font-size:1.3em; text-align:center; }
table td:nth-child(3) { font-size:0.8em; color:gray; }
</style>

<table>
<tr class=done>
	<td>Build App Framework</td>
	<td>done</td>
	<td>https://svn.geograph.org.uk/svn/branches/toy/</td>
</tr>

<tr class=done>
	<td>Get running on single webserver - PHP5.6</td>
	<td>done</td>
	<td>here! http://toy.geograph.org.uk/ </td>
</tr>

<tr class=done>
	<td>Implement all backends</td>
	<td>done</td>
	<td>File Storage, MySQL+ADOdb, Sphinx/Manticore, Redis/Memcache, Smarty, Carrot2, Timegate, Cron Running</td>
</tr>

<tr class=done>
	<td>Build automated Test</td>
	<td>done</td>
	<td>http://toy.geograph.org.uk/test.php</td>
</tr>

<tr>
	<td>Upgrade code to support and run on PHP7</td>
	<td>todo</td>
	<td>Currently using many legacy functions, like mysql_*, need tweaking to run on PHP7</td>
</tr>

<tr>
	<td>Implement file storage with Amazon S3 API</td>
	<td>todo</td>
	<td>currently could run with s3fs client, but can be upgrated to bypass that and use Amazon S3 API directly</td>
</tr>

<tr>
	<td>Try building from fresh on new VM</td>
	<td>todo</td>
	<td>To check the code in the repository has everything to get going</td>
</tr>

<tr>
	<td>Try deploying on multiple webservers</td>
	<td>todo</td>
	<td>will proabbly need additional checks to make sure distributed filesystem (for smarty) works</td>
</tr>

<!--
<tr class=done>
	<td></td>
	<td>done</td>
	<td></td>
</tr>

<tr>
	<td></td>
	<td>todo</td>
	<td></td>
</tr>
-->

</table>
