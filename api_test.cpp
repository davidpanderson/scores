#include "api.h"

int main(int, char**) {
    FILTERS filters;
    int offset=0, limit=99999999;
    int bits_out[BITMAP_MAX_NWORDS];
    BITMAP& bitmap_out = (BITMAP&)bits_out;
    bitmap_out.clear(BITMAP_MAX_NWORDS);
    set<int> set_out;
    int result_type;

    switch (0) {
    case 0:
        filters.instrument_ids.push_back(115);  // piano
        filters.instrument_ids.push_back(166);  // violin
        filters.period_ids.push_back(2);
        filters.pub_year_min = 1700;
        filters.pub_year_max = 1850;
        filters.keywords.push_back("Mozart");
        result_type = WORK_TYPE_ID;
        break;
        for (int i=0; i<100; i++) {
            scan_works(
                filters, result_type, offset, limit, bitmap_out, set_out
            );
        }
        break;
    case 1:
        //filters.license_id = 35;        // Creative commons
        filters.license_id = 1;        // Public Domain
        filters.composer_sex = 2;
        result_type = SCORE_ID;
        for (int i=0; i<100; i++) {
            scan_scores(
                filters, result_type, offset, limit, bitmap_out, set_out
            );
        }
        break;
    case 2:
        filters.license_id = 7;        // Creative Commons Attribution-NonCommercial-NoDerivs 4.0
        //filters.license_id = 1;        // Public Domain
        //filters.comp_sex = 2;
        result_type = WORK_ID;
        limit = 100;
        for (int i=0; i<100; i++) {
            scan_recordings(
                filters, result_type, offset, limit, bitmap_out, set_out
            );
        }
        break;
    case 3:
        COMBO_SPEC spec;
        INST_COMBO ic;

        int i=0;
        ic.inst_id[i] = 1; ic.count[i] = 1;
        i++;
        ic.inst_id[i] = 3; ic.count[i] = 2;
        i++;
        ic.inst_id[i] = 5; ic.count[i] = 2;
        i++;
        ic.inst_id[i] = 0;

        spec.add_inst(1,1,1);
        spec.add_inst(3,1,5);
        spec.others_ok = true;

        if (spec.matches(ic)) {
            printf("match\n");
        } else {
            printf("fail\n");
        }
        break;
    }
}
