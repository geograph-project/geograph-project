  </head>
  <body <?php if (isset($_GET['walk'])||isset($_GET['osos'])) print(' onload="initmap()"'); ?>>
<!-- Skip to content if no style sheet loaded -->
    <div id="skip" class="noscr">
      <a href="#content_col">Skip to content</a>
    </div>
<!-- Header bar -->
    <div id="header">
      <h1><?php print($hdr2); ?></h1>
      <div class="noscr">
        <a href="/ruw/"><img title="Two GISAXS patterns - see under research for details." alt="" src="/ruw/templates/gisaxs_120x60.png" /></a>
        <h2><?php print($hdr1); ?></h2>
      </div>
    </div>
<!-- Local copy warning -->
<?php
    if ($_SERVER['SERVER_ADDR']=='127.0.0.1') {
?>
      <div style="position:fixed;top:25px;left:50%;z-index:9;padding:5px;margin:5px;background:yellow;font-size:1.2em;font-weight:bold">
        <a href="http://users.aber.ac.uk<?php print($_SERVER['PHP_SELF']); ?>" target="_blank" style="color:black">LOCAL COPY</a>
      </div>
<?php
    }
?>
<!-- Prev/next arrows where available -->
    <?php if ($prev||$next) { ?>
      <div class="switch next">
        <a href="<?php print($prev); ?>">[&lt;]</a> 
        <a href="<?php print($next); ?>">[&gt;]</a>
      </div>
    <?php } ?>
<!-- English/Welsh switch where available -->
    <div>
      <?php
        if ($cym==1) {
          printf('<a class="switch cym noscr" href="%s">[Cymraeg]</a>',preg_replace('/.php/','_cym.php',$_SERVER['PHP_SELF']));
        } elseif ($cym==2) {
          printf('<a class="switch cym noscr" href="%s">[English]</a>',preg_replace('/_cym.php/','.php',$_SERVER['PHP_SELF']));
        }
      ?>
    </div>
<!-- Main navigation -->
    <div id="nav_col" class="noscr">
      <div id="nav" class="panel">
        <ul>
          <li><a href="/ruw/">Home</a></li>
          <li><a href="/ruw/olds.php">News</a></li>
          <li>Research
            <ul>
              <!--li>ASAXS</li>
              <li>GISAXS</li>
              <li>Diffraction</li>
              <li>Ceramics</li>
              <li>Sol-gel</li>
              <li>Stress&amp;strain</li>
              <li>Applications</li>
              <li>People</li>
              <li>Grants</li-->
              <li><a href="/ruw/res/beam">Beamtimes</a></li>
              <li><a href="/ruw/res/pap">Papers</a></li>
              <!--li>CV</li-->
            </ul>
          </li>
          <li><a href="/ruw/teach">Teaching</a>
            <ul>
              <!--li><a href="/ruw/teach/120/">ph120<br />Classical Phys.</a></li-->
              <li><a href="/ruw/teach/237/">ph237<br />Quantum Phys.</a></li>
              <li><a href="/ruw/teach/260/">mp260<br />Mathem. Phys.</a></li>
              <li><a href="/ruw/teach/334/">ph334<br />Condensed Matter</a></li>
              <li><a href="/ruw/teach/340/">ph340<br />Adv. Techniques</a></li>
              <li><a href="/ruw/teach/350/">ph35x<br />BSc project</a></li>
            </ul>
          </li>
          <li><a href="/ruw/walk/?walk">Walking in Wales</a></li>
          <li><a href="/ruw/misc/">Other Diversions</a></li>
        </ul>
      </div>
<!-- Context navigation where available -->
      <?php
        $dir=str_replace('/ruw','',$dir);   // both docroot and dir contain /ruw
        if (file_exists($docroot.$dir.'/contents.php')) {
      ?>
      <div id="context" class="panel">
        <?php if ($cym==2) { ?>
          <b>Cynnwys y rhan yma</b>
        <?php } else { ?>
          <b>In this section</b>
        <?php } ?>
        <hr />
        <ul>
          <?php if ($cym==2) {
            include($docroot.$dir.'/contents_cym.php');
          } else {
            include($docroot.$dir.'/contents.php');
          } ?>
        </ul>
      </div>
      <?php } ?>
<!-- Contact box -->
      <div id="contact" class="panel">
        <b>Dr Rudolf Winter</b>
        <hr />
        Materials Physics<br />
        Aberystwyth University<br />
        Penglais<br />
        Aberystwyth<br />
        SY23 3BZ<br />
        Wales<br />
        <hr />
        Ffiseg Defnyddiau<br />
        Prifysgol Aberystwyth<br />
        Penglais<br />
        Aberystwyth<br />
        SY23 3BZ<br />
        Cymru<br />
        <hr />
        <a href="mailto:ruw@aber.ac.uk">ruw@aber.ac.uk</a><br />
        <hr />
        <img src="/ruw/templates/sunset.jpg" title="" alt="" />
      </div>
    </div>
<!-- Content, at last -->
    <div id="content_col">
