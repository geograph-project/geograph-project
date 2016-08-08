schema.mysql
 This is the main schema. All tables are empty, just contains the structure. Import this first. It is always needed

changes.mysql
 This potentially contains changes to main schema. Where code has been updatd, but schema.mysql not regenerated. Run these commands if present. 

article_cat.mysql
geobb_forums.mysql.bz2
 Just some basic data to help get started. Not needed, but might help. 

gridprefix.mysql.bz2
gridsquare.mysql.bz2
rockall.mysql
 These give you basic data for a Geograph Britain & Ireland project. Use these to get a nice blank landmap, great for experimentation. 

loc_towns.mysql.bz2
loc_towns_changes.txt
 Really basic gazetteer, its needed as plotting maps, so recommended to import if use the gridsquare table above

loc_adm1.mysql.bz2
loc_dsg.mysql.bz2
loc_counties.mysql.bz2
loc_placenames.mysql.bz2
loc_postcodes.mysql.bz2
loc_ppl.zip
loc_wikipedia.mysql.bz2
 These initialalize a basic Britain & Ireland Gazetter. Not needed for a running project, but make searching by placename/postcode etc possible 

gridprefix_creation.txt
 Demo of how gridprefix was crated for B&I. DONT IMPORT. just use as an example. 
