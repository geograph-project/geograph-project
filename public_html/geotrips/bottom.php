<!-- Next button at bottom of lecture screen -->
      <a href="<?php print($next); ?>" class="screen" style="position:relative;top:-750px">[next]</a>
<!-- Footer -->
      <div id="footer" class="noscr">
        <hr />
        <a href="http://jigsaw.w3.org/css-validator/validator?uri=http%3A%2F%2Fusers.aber.ac.uk%2Fruw%2Ftemplates%2Fmain.css"><img class="flt_r" alt="Valid CSS!" src="/ruw/templates/vcss.gif" height="20" /></a>
        <a href="http://validator.w3.org/check?uri=referer"><img class="flt_r" alt="Valid XHTML 1.0 Strict" src="/ruw/templates/valid-xhtml10.png" height="20" /></a>
<?php
if ($cym==2) {
  print('<em>Arna i mae\'r bai, nid ar fy nghyflogwr i, ac ati, ac ati...</em><br />');
  if ($lastmod) print("Newidwyd y cynnwys: $lastmod; ");
  else print('Mae\'r dudalen hon wedi ei chreu yn ddeinamig o gronfa ddata; ');
  if (!$gan) $gan='<a href="http://www.ifa.hawaii.edu/users/hmorgan/index-huwc.htm">Huw Morgan</a>';
  print("<br /><br />Cyfieithwyd $cyfieithwyd gan $gan.");
} else {
  print('<em>Blame me, not my employer etc. etc.</em><br />');
  if ($lastmod) print("Content of this page last modified: $lastmod");
  else print('Page dynamically created from database.');
}
?>
      </div>
    </div>
  </body>
</html>
