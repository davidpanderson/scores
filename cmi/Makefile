# initialize DB manually, e.g.
#   mysql < create_dev.sql
#   mysql cmi_dev < schema.sql

all: data/make_ser_done

data/init_done:
	populate_init.php && touch data/init_done

data/location_done: data/init_done
	locations.php && touch data/location_done

data/lang_done: data/location_done
	populate_lang.php && touch data/lang_done

data/inst_done: data/lang_done
	populate_inst.php && touch data/inst_done

data/composition_type_done: data/inst_done
	populate_comp_type.php && touch data/composition_type_done

data/category_done: data/composition_type_done
	populate_category.php > cat.out && touch data/category_done

data/comp_done: data/category_done
	populate_comp.php > pc.out && touch data/comp_done

data/make_ser_done: data/comp_done
	make_ser.php && touch data/make_ser_done

clean:
	rm -f data/*_done
