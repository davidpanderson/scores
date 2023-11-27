#ifndef _API_H
#define _API_H

#include <cstdio>
#include <cstring>
#include <cstdlib>
#include <sys/mman.h>
#include <fcntl.h>
#include <set>
#include <vector>
#include <algorithm>

#include "digest.h"

using std::set;
using std::vector;

#define DEBUG   0
#if DEBUG
    #define DEBUG_PRINT(x) printf(x); printf("\n");
#else
    #define DEBUG_PRINT(x)
#endif

// which table to scan
//
#define WORKS       1
#define SCORES      2
#define RECORDINGS  3

// what to return
// Some type are returned in a std::set; the others in a BITMAP

#define WORK_ID         1       // set
#define SCORE_ID        2       // set
#define RECORDING_ID    3       // set
#define INSTRUMENT_ID   4
#define COMPOSER_ID     5       // set
#define NATIONALITY_ID  6
#define PERIOD_ID       7
#define WORK_TYPE_ID    8
#define PUBLISHER_ID    9       // set

// instrument combo specification
// selects a subset of instrument combos

// a combo spec is a list of instrument specs:
//
struct COMBO_SPEC_INST {
    int inst_id;
    int count_min;
    int count_max;
};

bool csi_compare(COMBO_SPEC_INST &c1, COMBO_SPEC_INST &c2) {
    return (c1.inst_id < c2.inst_id);
}

struct COMBO_SPEC {
    vector<COMBO_SPEC_INST> insts;  // sorted by ID
    bool others_ok;

    COMBO_SPEC() {
        others_ok = false;
    }

    void add_inst(int inst_id, int count_min, int count_max) {
        COMBO_SPEC_INST csi;
        csi.inst_id = inst_id;
        csi.count_min = count_min;
        csi.count_max = count_max;
        insts.push_back(csi);
    }

    void sort() {
        std::sort(insts.begin(), insts.end(), csi_compare);
    }

    bool present() {
        return insts.size()>0;
    }

    // does the given instrument combo match this spec?
    //
    inline bool matches(INST_COMBO& combo) {
        int combo_ind=0;    // index into combo
        int spec_ind=0;     // index into spec
        int spec_n = insts.size();
        while (1) {
            int combo_id = combo.inst_id[combo_ind];
            if (!combo_id) {
                // reached end of combo.
                // if spec has others insts with nonzero min, match fails
                while (spec_ind < spec_n) {
                    if (insts[spec_ind].count_min) return false;
                    spec_ind++;
                }
                return true;
            }
            if (spec_ind == spec_n) {
                // reached end of spec, but not end of combo
                return others_ok;
            }
            COMBO_SPEC_INST &csi = insts[spec_ind];
            if (combo_id == csi.inst_id) {
                if (combo.count[combo_ind] < csi.count_min) return false;
                if (combo.count[combo_ind] > csi.count_max) return false;
                combo_ind++;
                spec_ind++;
            } else if (combo.inst_id[combo_ind] < csi.inst_id) {
                // combo has an instrument that spec doesn't
                if (!others_ok) return false;
                combo_ind++;
            } else {
                // spec has an instrument that combo doesn't
                if (csi.count_min) return false;
                spec_ind++;
            }
        }
        printf("COMBO_SPEC::matches error\n"); exit(1);
    }
};

// a set of filters that limit the items scanned.
// Not all filters apply to all table types
//
struct FILTERS {
    int pub_year_min, pub_year_max;
    vector<int> instrument_ids;
    vector<int> inst_combo_ids;
    vector<int> period_ids;
    vector<int> work_type_ids;
    vector<const char*> keywords;
    int composer_nationality;
    int composer_sex;
    int publisher_id;
    int license_id;
    bool arrangements_only;
    COMBO_SPEC inst_spec;

    // derived data
    //
    bool have_insts;
    int inst_bits[INSTRUMENT_NWORDS];
    bool have_periods;
    int period_bits[PERIOD_NWORDS];
    bool have_work_types;
    int work_type_bits[WORK_TYPE_NWORDS];
    bool have_inst_combos;
    int inst_combo_bits[INST_COMBO_NWORDS];
    bool have_inst_spec;

    unsigned int nkeywords;

    FILTERS(){
        pub_year_min = 0;
        pub_year_max = 99999;
        composer_nationality = 0;
        composer_sex = 0;
        publisher_id = 0;
        license_id = 0;
        arrangements_only = false;
    }

    // convert vectors to bitmaps
    //
    void init_bitmaps() {
        have_insts=false;
        BITMAP &instrument_bitmap = (BITMAP&)inst_bits;
        instrument_bitmap.clear(INSTRUMENT_NWORDS);
        for (unsigned int i=0; i<instrument_ids.size(); i++) {
            instrument_bitmap.set(instrument_ids[i]);
            have_insts = true;
        }

        have_periods=false;
        BITMAP &period_bitmap = (BITMAP&)period_bits;
        period_bitmap.clear(PERIOD_NWORDS);
        for (unsigned int i=0; i<period_ids.size(); i++) {
            period_bitmap.set(period_ids[i]);
            have_periods = true;
        }

        have_work_types=false;
        BITMAP &work_type_bitmap = (BITMAP&)work_type_bits;
        work_type_bitmap.clear(WORK_TYPE_NWORDS);
        for (unsigned int i=0; i<work_type_ids.size(); i++) {
            work_type_bitmap.set(work_type_ids[i]);
            have_work_types = true;
        }

        have_inst_combos = false;
        BITMAP &inst_combo_bitmap = (BITMAP&)inst_combo_bits;
        inst_combo_bitmap.clear(INST_COMBO_NWORDS);

        // the set of instrument combos can be specified explicitly

        for (unsigned int i=0; i<inst_combo_ids.size(); i++) {
            inst_combo_bitmap.set(inst_combo_ids[i]);
            have_inst_combos = true;
        }

        // ... or via an instrumentation specification

        if (inst_spec.present()) {
            inst_spec.sort();
            size_t sz = file_size("digest_ic.bin");
            int fd = open("digest_ic.bin", O_RDONLY);
            if (fd<0) {
                printf("missing digest_ic.bin\n");
                exit(1);
            }
            void *p = mmap(0, sz, PROT_READ, MAP_SHARED, fd, 0);
            int nics = sz/sizeof(INST_COMBO);
            INST_COMBO *ics = (INST_COMBO*) p;
            int n = 0;
            for (int i=0; i<nics; i++) {
                INST_COMBO &ic = ics[i];
                if (inst_spec.matches(ic)) {
                    inst_combo_bitmap.set(ic.id);
                    n++;
                }
            }
#if DEBUG
            printf("%d inst combos match spec\n", n);
#endif
            have_inst_combos = true;
        }

        nkeywords = keywords.size();
    }

    inline bool work_is_ok(WORK& work) {
#if DEBUG
        printf("work_is_ok: %s\n", work.title);
#endif
        if (nkeywords) {
            bool found_all = true;
            for (unsigned int i=0; i<nkeywords; i++) {
                if (!strstr(work.title, keywords[i])) {
                    found_all = false;
                    break;
                }
            }
            if (!found_all) {
                DEBUG_PRINT("keywords")
                return false;
            }
        }
        if (have_work_types) {
            BITMAP &work_type_bitmap = (BITMAP&)work_type_bits;
            BITMAP &bm2 = (BITMAP&)work.work_type;
            if (!work_type_bitmap.overlaps(bm2, WORK_TYPE_NWORDS)) {
                DEBUG_PRINT("work types")
                return false;
            }
        }
        if (have_periods) {
            BITMAP &period_bitmap = (BITMAP&)period_bits;
            if (!period_bitmap.is_set(work.period_id)) {
                DEBUG_PRINT("periods")
                return false;
            }
        }
        if (have_insts) {
            BITMAP *ib = (BITMAP*)work.inst;
            BITMAP &instrument_bitmap = (BITMAP&)inst_bits;
            if (!ib->contains(instrument_bitmap, INSTRUMENT_NWORDS)) {
                DEBUG_PRINT("instrument bitmap")
                return false;
            }
        }
        if (have_inst_combos) {
            bool found = false;
            BITMAP &inst_combo_bitmap = (BITMAP&)inst_combo_bits;
            for (int i=0; i<MAX_COMBOS; i++) {
                if (!work.inst_combos[i]) break;
                if (inst_combo_bitmap.is_set(work.inst_combos[i])) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                DEBUG_PRINT("instrument combos")
                return false;
            }
        }
        if (composer_nationality) {
            BITMAP &nb = (BITMAP&)work.comp_nationality;
            if (!nb.is_set(composer_nationality)) {
                DEBUG_PRINT("nationality")
                return false;
            }
        }
        if (composer_sex && (composer_sex != work.comp_sex)) {
            DEBUG_PRINT("sex")
            return false;
        }

        int y = work.year_of_first_publication;
        if (y<pub_year_min) return false;
        if (y>pub_year_max) return false;

        return true;
    }

    inline bool score_is_ok(SCORE& score) {
        if (nkeywords) {
            bool found_all = true;
            for (unsigned int i=0; i<nkeywords; i++) {
                if (!strstr(score.title, keywords[i])) {
                    found_all = false;
                    break;
                }
            }
            if (!found_all) {
                DEBUG_PRINT("keywords")
                return false;
            }
        }
        if (have_work_types) {
            BITMAP &work_type_bitmap = (BITMAP&)work_type_bits;
            BITMAP &bm2 = (BITMAP&)score.work_type;
            if (!work_type_bitmap.overlaps(bm2, WORK_TYPE_NWORDS)) {
                return false;
            }
        }
        if (have_periods) {
            BITMAP &period_bitmap = (BITMAP&)period_bits;
            if (!period_bitmap.is_set(score.period_id)) {
                DEBUG_PRINT("periods")
                return false;
            }
        }
        if (have_insts) {
            BITMAP *ib = (BITMAP*)score.inst;
            BITMAP &instrument_bitmap = (BITMAP&)inst_bits;
            if (!ib->contains(instrument_bitmap, INSTRUMENT_NWORDS)) {
                return false;
            }
        }
        if (have_inst_combos) {
            bool found = false;
            BITMAP &inst_combo_bitmap = (BITMAP&)inst_combo_bits;
            for (int i=0; i<MAX_COMBOS; i++) {
                if (!score.inst_combos[i]) break;
                if (inst_combo_bitmap.is_set(score.inst_combos[i])) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }
        if (composer_nationality) {
            BITMAP &nb = (BITMAP&)score.comp_nationality;
            if (!nb.is_set(composer_nationality)) {
                return false;
            }
        }
        if (composer_sex && (composer_sex != score.comp_sex)) {
            return false;
        }
        if (license_id) {
            if (license_id != score.license_id) {
                return false;
            }
        }
        if (arrangements_only) {
            if (!score.is_arrangement) {
                return false;
            }
        }
        if (publisher_id && (publisher_id != score.publisher_id)) {
            return false;
        }
        return true;
    }

    inline bool rec_is_ok(REC& rec) {
        if (nkeywords) {
            bool found_all = true;
            for (unsigned int i=0; i<nkeywords; i++) {
                if (!strstr(rec.title, keywords[i])) {
                    found_all = false;
                    break;
                }
            }
            if (!found_all) {
                DEBUG_PRINT("keywords")
                return false;
            }
        }
        if (have_work_types) {
            BITMAP &work_type_bitmap = (BITMAP&)work_type_bits;
            BITMAP &bm2 = (BITMAP&)rec.work_type;
            if (!work_type_bitmap.overlaps(bm2, WORK_TYPE_NWORDS)) {
                return false;
            }
        }
        if (have_periods) {
            BITMAP &period_bitmap = (BITMAP&)period_bits;
            if (!period_bitmap.is_set(rec.period_id)) {
                DEBUG_PRINT("periods")
                return false;
            }
        }
        if (have_insts) {
            BITMAP *ib = (BITMAP*)rec.inst;
            BITMAP &instrument_bitmap = (BITMAP&)inst_bits;
            if (!ib->contains(instrument_bitmap, INSTRUMENT_NWORDS)) {
                return false;
            }
        }
        if (have_inst_combos) {
            bool found = false;
            BITMAP &inst_combo_bitmap = (BITMAP&)inst_combo_bits;
            for (int i=0; i<MAX_COMBOS; i++) {
                if (!rec.inst_combos[i]) break;
                if (inst_combo_bitmap.is_set(rec.inst_combos[i])) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                return false;
            }
        }
        if (composer_nationality) {
            BITMAP &nb = (BITMAP&)rec.comp_nationality;
            if (!nb.is_set(composer_nationality)) {
                return false;
            }
        }
        if (composer_sex && (composer_sex != rec.comp_sex)) {
            return false;
        }
        if (license_id) {
            if (license_id != rec.license_id) {
                return false;
            }
        }
        if (arrangements_only) {
            if (!rec.is_arrangement) {
                return false;
            }
        }
        return true;
    }
};

// scan the set of works, filtering, and return bitmap or set.
// clearing these is caller's responsibility
//
void scan_works(
    FILTERS &filters,
    int result_type,
    int offset, int limit,
    BITMAP &bitmap_out,
    set<int> &set_out
) {
    size_t sz = file_size("digest_work.bin");
    int fd = open("digest_work.bin", O_RDONLY);
    if (fd<0) {
        printf("missing digest_work.bin\n");
        exit(1);
    }
    void *p = mmap(0, sz, PROT_READ, MAP_SHARED, fd, 0);
    int nworks = sz/sizeof(WORK);
    WORK *works = (WORK*) p;

    int nok = 0;
    for (int i=0; i<nworks; i++) {
        WORK &work = works[i];
        if (!filters.work_is_ok(work)) continue;
        switch (result_type) {
        case WORK_ID:
            if (nok<offset) break;
            set_out.insert(work.work_id);
            if (set_out.size() >= limit) return;
            break;
        case INSTRUMENT_ID:
            bitmap_out.set_multi(work.inst, INSTRUMENT_NWORDS);
            break;
        case COMPOSER_ID:
            if (nok<offset) break;
            set_out.insert(work.comp_id);
            if (set_out.size() >= limit) return;
            break;
        case NATIONALITY_ID:
            bitmap_out.set_multi(work.comp_nationality, NATIONALITY_NWORDS);
            break;
        case PERIOD_ID:
            bitmap_out.set(work.period_id);
            break;
        case WORK_TYPE_ID:
            bitmap_out.set_multi(work.work_type, WORK_TYPE_NWORDS);
            break;
        }
        nok++;
    }
}

void scan_scores(
    FILTERS &filters,
    int result_type,
    int offset, int limit,
    BITMAP &bitmap_out,
    set<int> &set_out
) {
    size_t sz = file_size("digest_score.bin");
    int fd = open("digest_score.bin", O_RDONLY);
    if (fd<0) {
        printf("missing digest_score.bin\n");
        exit(1);
    }
    void *p = mmap(0, sz, PROT_READ, MAP_SHARED, fd, 0);
    int nscores = sz/sizeof(SCORE);
    SCORE *scores = (SCORE*) p;
    int nok = 0;
    for (int i=0; i<nscores; i++) {
        SCORE &score = scores[i];
        if (!filters.score_is_ok(score)) continue;
        switch (result_type) {
        case WORK_ID:
            if (nok<offset) break;
            set_out.insert(score.work_id);
            if (set_out.size() >= limit) return;
            break;
        case SCORE_ID:
            if (nok<offset) break;
            set_out.insert(score.score_id);
            if (set_out.size() >= limit) return;
            break;
        case INSTRUMENT_ID:
            bitmap_out.set_multi(score.inst, INSTRUMENT_NWORDS);
            break;
        case COMPOSER_ID:
            if (nok<offset) break;
            set_out.insert(score.comp_id);
            if (set_out.size() >= limit) return;
            break;
        case NATIONALITY_ID:
            bitmap_out.set_multi(score.comp_nationality, NATIONALITY_NWORDS);
            break;
        case PERIOD_ID:
            bitmap_out.set(score.period_id);
            break;
        case WORK_TYPE_ID:
            bitmap_out.set_multi(score.work_type, WORK_TYPE_NWORDS);
            break;
        case PUBLISHER_ID:
            if (score.publisher_id) {
                set_out.insert(score.publisher_id);
            }
            break;
        }
        nok++;
    }
}

void scan_recordings(
    FILTERS& filters,
    int result_type,
    int offset, int limit,
    BITMAP &bitmap_out,
    set<int> &set_out
) {
    size_t sz = file_size("digest_rec.bin");
    int fd = open("digest_rec.bin", O_RDONLY);
    if (fd<0) {
        printf("missing digest_rec.bin\n");
        exit(1);
    }
    void *p = mmap(0, sz, PROT_READ, MAP_SHARED, fd, 0);
    int nrecs = sz/sizeof(REC);
    REC *recs = (REC*) p;
    int nok=0;
    for (int i=0; i<nrecs; i++) {
        REC &rec = recs[i];
        if (!filters.rec_is_ok(rec)) continue;
        switch (result_type) {
        case WORK_ID:
            if (nok<offset) break;
            set_out.insert(rec.work_id);
            if (set_out.size() >= limit) return;
            break;
        case RECORDING_ID:
            if (nok<offset) break;
            set_out.insert(rec.rec_id);
            if (set_out.size() >= limit) return;
            break;
        case INSTRUMENT_ID:
            bitmap_out.set_multi(rec.inst, INSTRUMENT_NWORDS);
            break;
        case COMPOSER_ID:
            if (nok<offset) break;
            set_out.insert(rec.comp_id);
            if (set_out.size() >= limit) return;
            break;
        case NATIONALITY_ID:
            bitmap_out.set_multi(rec.comp_nationality, NATIONALITY_NWORDS);
            break;
        case PERIOD_ID:
            bitmap_out.set(rec.period_id);
            break;
        case WORK_TYPE_ID:
            bitmap_out.set_multi(rec.work_type, WORK_TYPE_NWORDS);
            break;
        }
        nok++;
    }
}

#endif
