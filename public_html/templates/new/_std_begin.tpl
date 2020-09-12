<!DOCTYPE HTML>
<!--
	Editorial by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>
	<head>
		{if $page_title}<title>{$page_title|escape:'html'} :: Geograph Britain and Ireland</title>
		{else}<title>Geograph Britain and Ireland - photograph every grid square!</title>{/if}

		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="/assets/css/main.css" />
		<link rel="stylesheet" href="/templates/new/css/content.css" />

		<link rel="shortcut icon" type="image/x-icon" href="{$static_host}/favicon.ico"/>
		<script type="text/javascript" src="{"/js/geograph.js"|revision}"></script>
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<div id="main">
						<div class="inner">

							<!-- Header -->
								<header id="header">
									{if $content_title}
										<a class="logo">{$content_title|escape:'html'}</a>
									{elseif $page_title}
										<a href="/" class="logo">{$page_title|escape:'html'}</a>
									{/if}
									<ul class="icons">
										<li><a href="#" class="icon fa-twitter"><span class="label">Twitter</span></a></li>
										<li><a href="#" class="icon fa-facebook"><span class="label">Facebook</span></a></li>
									</ul>
								</header>

							<!-- Section -->
								<section>



