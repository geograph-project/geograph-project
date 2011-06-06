<?php
$lookup=array(0=>"SV",10=>"SW",20=>"SX",30=>"SY",40=>"SZ",50=>"TV",60=>"TW",1=>"SQ",11=>"SR",21=>"SS",31=>"ST",41=>"SU",51=>"TQ",61=>"TR",2=>"SL",12=>"SM",22=>"SN",32=>"SO",42=>"SP",52=>"TL",62=>"TM",3=>"SF",13=>"SG",23=>"SH",33=>"SJ",43=>"SK",53=>"TF",63=>"TG",4=>"SA",14=>"SB",24=>"SC",34=>"SD",44=>"SE",54=>"TA",64=>"TB",5=>"NV",15=>"NW",25=>"NX",35=>"NY",45=>"NZ",55=>"OV",65=>"OW",6=>"NQ",16=>"NR",26=>"NS",36=>"NT",46=>"NU",56=>"OQ",66=>"OR",7=>"NL",17=>"NM",27=>"NN",37=>"NO",47=>"NP",57=>"OL",67=>"OM",8=>"NF",18=>"NG",28=>"NH",38=>"NJ",48=>"NK",58=>"OF",68=>"OG",9=>"NA",19=>"NB",29=>"NC",39=>"ND",49=>"NE",59=>"OA",69=>"OB",110=>"HW",210=>"HX",310=>"HY",410=>"HZ",111=>"HR",211=>"HS",311=>"HT",411=>"HU",112=>"HM",212=>"HN",312=>"HO",412=>"HP");

  $db=sqlite_open('../db/geotrips.db');
  $trk=sqlite_fetch_all(sqlite_query($db,"select * from geotrips order by id desc"));
  sqlite_close($db);
  print("TripID,Title,SubTitle,UserID,GridimageID,GridReference,TripDate,Updated,Content,SearchID\n");
  foreach ($trk as $trip) {
    if ($trip['title']) $title=$trip['title'];
    else $title=$trip['location'].' from '.$trip['start'];
    if (strlen($trip['descr'])>500) $subtit=substr($trip['descr'],0,500).'...';
    else $subtit=$trip['descr'];
    $gr=bbox2gr($trip['bbox']);
    print($trip['id'].',"'.$title.'","'.$subtit.'",'.$trip['uid'].','.$trip['img'].','.$gr.','.$trip['date'].',');
    print($trip['updated'].',"'.$trip['descr'].'",'.$trip['search']."\n");
  }

function bbox2gr($bbox) {
  global $lookup;
  $bbox=explode(' ',$bbox);
  $ee=(int)(($bbox[0]+$bbox[2])/2000.);
  $nn=(int)(($bbox[1]+$bbox[3])/2000.);
  $myr=10*(int)($ee/100.)+(int)($nn/100.);
  $myr=$lookup[$myr];
  $ee-=(100*(int)($ee/100.));
  $nn-=(100*(int)($nn/100.));
  $gr=sprintf("%2s%02s%02s",$myr,$ee,$nn);
  return $gr;
}

?>
