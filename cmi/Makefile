# do mysql < schema.sql manually

init_done:
	populate_init.php && touch init_done
location_done: init_done
	locations.php && touch location_done
lang_done:
	populate_lang.php && touch lang_done
inst_done:
	populate_inst.php && touch inst_done
composition_type_done:
	populate_comp_type.php && touch composition_type_done
category_done: init_done location_done
	populate_category.php && touch category_done
comp_done: inst_done category_done
	populate_comp.php && touch comp_done

clean:
	rm -f *_done
