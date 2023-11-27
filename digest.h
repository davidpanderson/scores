#ifndef _DIGEST_H_
#define _DIGEST_H_

// a set of IDs, represented as a bitmap.

#define WORK_TYPE_MAX_ID    377
#define WORK_TYPE_NWORDS    12

#define INSTRUMENT_MAX_ID   173
#define INSTRUMENT_NWORDS   6

#define PERIOD_MAX_ID       35
#define PERIOD_NWORDS       2

#define NATIONALITY_MAX_ID  126
#define NATIONALITY_NWORDS  4

// max of the above cases
#define BITMAP_MAX_NWORDS   12
#define BITMAP_MAX_ID       377

#define INST_COMBO_MAX_ID   11218
#define INST_COMBO_NWORDS   351

struct BITMAP {
    int bits[0];

    inline void clear(int nwords) {
        for (int i=0; i<nwords; i++) {
            bits[i] = 0;
        }
    }

    inline void set(int i) {
        bits[i/32] |= 1<<(i%32);
    }

    inline bool is_set(int i) {
        return bits[i/32] & 1<<(i%32);
    }

    // is ib2 a subset of this?
    inline bool contains(BITMAP& ib2, int nwords) {
        for (int i=0; i<nwords; i++) {
            int b = ib2.bits[i];
            if ((b & bits[i]) != b) return false;
        }
        return true;
    }

    // does ib2 overlap this?
    inline bool overlaps(BITMAP& ib2, int nwords) {
        for (int i=0; i<nwords; i++) {
            int b = ib2.bits[i];
            if (b & bits[i]) return true;
        }
        return false;
    }

    // is ib2 equal to this?
    inline bool equals(BITMAP& ib2, int nwords) {
        for (int i=0; i<nwords; i++) {
            if (ib2.bits[i] != bits[i]) return false;
        }
        return true;
    }

    // OR ib2 into this bitmap
    //
    inline void set_multi(int* ib2, int nwords) {
        for (int i=0; i<nwords; i++) {
            bits[i] |= ib2[i];
        }
    }
};

// info from the work, score and recording tables,
// together with info from connected tables like composer.
//
// Depending on the query, a field can be part of a filter
// or part of the result.
//

// max # of instrument combos to store.

#define MAX_COMBOS 4

struct WORK {
    char title[80];     // lower case
    int work_id;
    int work_type[WORK_TYPE_NWORDS];
    int period_id;
    int inst[INSTRUMENT_NWORDS];
        // the union of the instruments in the work's inst combos
    int inst_combos[MAX_COMBOS];
        // instrument combo ids; 0 marks end of list
    int comp_id;
    int comp_nationality[NATIONALITY_NWORDS];
    int comp_sex;
    int year_of_first_publication;
};

struct SCORE {
    char title[80];
    int score_id;
    int work_id;
    int work_type[WORK_TYPE_NWORDS];
    int period_id;
    int inst[INSTRUMENT_NWORDS];
        // if the score is an arrangement, the union of the combo insts
        // else the work's instruments as above
    int inst_combos[MAX_COMBOS];
        // score inst combo, else work
    int comp_id;
    int comp_nationality[NATIONALITY_NWORDS];
    int comp_sex;
    int license_id;
    int is_arrangement;
    int publisher_id;
};

struct REC {
    char title[80];
    int rec_id;
    int work_id;
    int work_type[WORK_TYPE_NWORDS];
    int period_id;
    int inst[INSTRUMENT_NWORDS];
        // if the rec is an arrangement, the union of the combo insts
        // else the work's instruments as above
    int inst_combos[MAX_COMBOS];
        // score inst combo, else work
    int comp_id;
    int comp_nationality[NATIONALITY_NWORDS];
    int comp_sex;
    int license_id;
    int is_arrangement;
};

// max #insts in a combo; digest.php prints this

#define MAX_INSTS   16

struct INST_COMBO {
    int id;
    int inst_id[MAX_INSTS+1];     // sorted; 0 marks end
    int count[MAX_INSTS];
};

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

size_t file_size(const char* path) {
    struct stat sbuf;
    stat(path, &sbuf);
    return sbuf.st_size;
}

#endif
