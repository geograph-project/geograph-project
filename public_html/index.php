<h2>Geograph Test / Reference Implementation</h2>

This is a basic implementation of Geograph Website code. It's <b>not</b> a proper user-facing website,
just enough code to test the backend services. We use this internally to to document, and test our
servers are capable of running the <a href="https://www.geograph.org.uk/">real Geograph website</a>.

<hr>
<style>ul li {padding:8px} </style>
<ul>

        <li><b><a href="https://svn.geograph.org.uk/viewsvn/?do=view&project=geograph&path=/branches/toy/README.txt">View README.txt</a></b></li>

	<li><a href="capabilities.txt">List of Capabilities of this implementation</a></li>

	<li><a href="test.php">Run Live Application Test</a><ul>

	<li>There is <a href="heartbeat.php">Automated Test</a> - its output doesn't show much - designed for powering a uptime/proxy check</li>

	</ul></li>

	<li><b><a href="source.php">View listing of files in this implementation</a></b><ul>
		<li>Key files:
			<a href="https://svn.geograph.org.uk/viewsvn/?do=view&project=geograph&path=/branches/toy/libs/conf/example.conf.php">Application Config</a>,
			<a href="https://svn.geograph.org.uk/viewsvn/?do=view&project=geograph&path=/branches/toy/public_html/test.php">Test Script</a>,
			<a href="https://svn.geograph.org.uk/viewsvn/?do=view&project=geograph&path=/branches/toy/config/apache.vhost.conf">Apache Config</a>
	</ul></li>

	<li>Subversion Repository: <a href="https://svn.geograph.org.uk/svn/branches/toy/">https://svn.geograph.org.uk/svn/branches/toy/</a><ul>

		<li><a href="https://svn.geograph.org.uk/viewsvn/?do=browse&project=geograph&path=/branches/toy/">View Code in SVN viewer</a></li>

		<li>Live Repository checkout:<br>
		<tt>svn checkout https://svn.geograph.org.uk/svn/branches/toy/ geograph_toy/</tt> </li>

		<li><a href="https://svn.geograph.org.uk/viewsvn/?do=log&project=geograph&path=/branches/toy/">View SVN Log</a></li>

        </ul></li>

	<li><a href="todo.php">Todo/Task List Progress</a></li>

</ul>

<hr>
Created by barry [at] geograph [dot org [dot] uk - contact me if any questions.
