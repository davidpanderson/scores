-- reset DB to before populate_comp.php
-- could also clear:
-- ensemble, person, person_role, organization, instrument_combo, license

delete from composition where id>0;
delete from score where id>0;
delete from performance where id>0;
delete from concert where id>0;
delete from _release where id>0;
delete from rating where id>0;
delete from review_useful where id>0;
delete from person_role where id>0;
