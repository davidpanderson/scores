// general fast query mechanism for API functions.
//
// this works as follows:
//
// there are C-format binary "digest" files for 3 tables:
//      works, scores, and recordings
//      Each item has some of the info from the corresponding SQL table,
//      plus some info from other tables (e.g. composer info)
// A query consists of:
//      which table to scan
//      a set of "filters" that select a subset of items
//      an optional limit on the number of IDs returned
//      a "result type" saying what to return - a set of IDs, either
//          a bitmap (for small tables like work type)
//          an list of IDs of works, scores or recordings
// To handle a query, the program
//      maps the appropriate binary file into memory
//      scans through the array,
//          skipping unselected items
//          and collecting the results from selected items
//      print the resulting IDs to stdout
//
// cmdline args:
//
//  --table_code N              table code (see above)
//  --result_type N             result type code (see above)
//  --offset N                  where to start in ID list
//  --limit N                   max # of IDs to return
//
// filters:
// '...' means can have multiple
//
//  --arrangements_only
//  --composer_nationality ...  composer nationality
//  --composer_sex N
//  --inst_combo N ...          instrument combo ID
//  --instrument N ...             instrument ID
//  --inst_spec id min max ...  part of instrumentation spec
//  --inst_spec_others_ok       part of instrumentation spec
//  --keyword X ...             title keyword
//  --period N ...              period ID
//  --pub_year lo hi            publication year range
//  --publisher ID              publisher ID
//  --work_type ID ...          work type ID

#include "digest.h"
#include "api.h"

int main(int argc, char** argv) {
    int table_code=0;
    int result_type=0;
    FILTERS filters;
    int offset=0, limit=99999999;
    int bits_out[BITMAP_MAX_NWORDS];
    BITMAP& bitmap_out = (BITMAP&)bits_out;
    bitmap_out.clear(BITMAP_MAX_NWORDS);
    set<int> set_out;

    for (int i=1; i<argc; i++) {
        if (!strcmp(argv[i], "--table")) {
           table_code = atoi(argv[++i]); 
        } else if (!strcmp(argv[i], "--result_type")) {
           result_type = atoi(argv[++i]); 
        } else if (!strcmp(argv[i], "--offset")) {
           offset = atoi(argv[++i]); 
        } else if (!strcmp(argv[i], "--limit")) {
           limit = atoi(argv[++i]); 

        // filter args follow

        } else if (!strcmp(argv[i], "--arrangements_only")) {
            filters.arrangements_only = true;
        } else if (!strcmp(argv[i], "--composer_nationality")) {
            filters.composer_nationality = atoi(argv[++i]);
        } else if (!strcmp(argv[i], "--composer_sex")) {
            filters.composer_sex = atoi(argv[++i]);
        } else if (!strcmp(argv[i], "--instrument")) {
            filters.instrument_ids.push_back(atoi(argv[++i]));
        } else if (!strcmp(argv[i], "--inst_combo")) {
            filters.inst_combo_ids.push_back(atoi(argv[++i]));
        } else if (!strcmp(argv[i], "--inst_spec")) {
            int id = atoi(argv[++i]);
            int min = atoi(argv[++i]);
            int max = atoi(argv[++i]);
            if (!max) max=999;
            filters.inst_spec.add_inst(id, min, max);
        } else if (!strcmp(argv[i], "--inst_spec_others_ok")) {
            filters.inst_spec.others_ok = true;
        } else if (!strcmp(argv[i], "--keyword")) {
            filters.keywords.push_back(argv[++i]);
        } else if (!strcmp(argv[i], "--period")) {
            filters.period_ids.push_back(atoi(argv[++i]));
        } else if (!strcmp(argv[i], "--pub_year")) {
            filters.pub_year_min = atoi(argv[++i]);
            filters.pub_year_max = atoi(argv[++i]);
        } else if (!strcmp(argv[i], "--publisher")) {
           filters.publisher_id = atoi(argv[++i]); 
        } else if (!strcmp(argv[i], "--work_type")) {
            filters.work_type_ids.push_back(atoi(argv[++i]));
        } else {
            printf("unknown arg %d %s\n", i, argv[i]);
            exit(1);
        }
    }
    filters.init_bitmaps();
    switch(table_code) {
    case WORKS:
        switch (result_type) {
        case SCORE_ID:
        case RECORDING_ID:
        case PUBLISHER_ID:
            printf("result type %d not supported for work table\n", result_type);
            exit(1);
        }
        scan_works(filters, result_type, offset, limit, bitmap_out, set_out);
        break;
    case SCORES:
        switch (result_type) {
        case RECORDING_ID:
            printf("result type %d not supported for score table\n", result_type);
            exit(1);
        }
        scan_scores(filters, result_type, offset, limit, bitmap_out, set_out);
        break;
    case RECORDINGS:
        switch (result_type) {
        case SCORE_ID:
        case PUBLISHER_ID:
            printf("result type %d not supported for recording table\n", result_type);
            exit(1);
        }
        scan_recordings(filters, result_type, offset, limit, bitmap_out, set_out);
        break;
    default:
        printf("bad table code %d\n", table_code);
        exit(1);
    }

    switch(result_type) {
    case INSTRUMENT_ID:
    case NATIONALITY_ID:
    case PERIOD_ID:
    case WORK_TYPE_ID:
        for (int i=1; i<BITMAP_MAX_ID; i++) {
            if (bitmap_out.is_set(i)) {
                printf("%d ", i);
            }
        }
        break;
    case WORK_ID:
    case SCORE_ID:
    case RECORDING_ID:
    case COMPOSER_ID:
    case PUBLISHER_ID:
        for (const int &id: set_out) {
            printf("%d ", id);
        }
        break;
    default:
        printf("bad result type code %d\n", result_type);
        exit(1);
    }
    printf("\n");
}
