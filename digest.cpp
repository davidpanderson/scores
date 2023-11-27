// read digest_*.txt and generate digest_*.bin

#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <vector>

#include "digest.h"

using std::vector;

// str is a string of integers, separated by spaces, ending with newline.
// parse it into a vector
//
inline void parse_ints(const char* str, vector<int> &vec) {
    char *p;
    vec.clear();
    while (1) {
        int i = (int)strtol(str, &p, 10);
        if (p == str) break;
        vec.push_back(i);
        if (!p) break;
        str = p;
    }
}

// str is a string of integers, separated by spaces, ending with newline
// Set the corresponding bits in b
//
inline void set_bits(const char* str, BITMAP& b, int max_id) {
    vector<int> vec;
    parse_ints(str, vec);
    for (int i: vec) {
        if (i>max_id) {
            fprintf(stderr, "bad ID %d\n", i);
            exit(1);
        }
        b.set(i);
    }
}

// str is a string of integers, separated by spaces, ending with newline
// Fill in up to MAX_COMBOS in the given int array
//
void set_inst_combos(const char* str, int* ids) {
    vector<int> vec;
    parse_ints(str, vec);
    for (int i=0; i<MAX_COMBOS; i++) {
        if (i < vec.size()) {
            ids[i] = vec[i];
        } else {
            ids[i] = 0;
        }
    }
}

#define BUF_SIZE    4096

void do_works() {
    FILE* fin = fopen("data/digest_work.txt", "r");
    FILE* fout = fopen("data/digest_work.bin", "w");
    char buf[BUF_SIZE];
    WORK work;
    int n, id;
    char *p, c;
    while (1) {
        p = fgets(buf, BUF_SIZE, fin);
        if (!p) break;
        strncpy(work.title, buf, sizeof(work.title));
        work.title[sizeof(work.title)-1] = 0;

        p = fgets(buf, BUF_SIZE, fin);
        work.work_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &wtb = (BITMAP&)work.work_type;
        wtb.clear(WORK_TYPE_NWORDS);
        set_bits(buf, wtb, WORK_TYPE_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        work.period_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &ib = (BITMAP&)work.inst;
        ib.clear(INSTRUMENT_NWORDS);
        set_bits(buf, ib, INSTRUMENT_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        set_inst_combos(buf, work.inst_combos);

        p = fgets(buf, BUF_SIZE, fin);
        work.comp_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &nb = (BITMAP&)work.comp_nationality;
        nb.clear(NATIONALITY_NWORDS);
        set_bits(buf, nb, NATIONALITY_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        work.comp_sex = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        work.year_of_first_publication = atoi(buf);

        //printf("wrote %s\n", work.title);
        fwrite(&work, sizeof(work), 1, fout);
    }
    fclose(fout);
    fclose(fin);
}

void do_scores() {
    FILE* fin = fopen("data/digest_score.txt", "r");
    FILE* fout = fopen("data/digest_score.bin", "w");
    char buf[BUF_SIZE];
    SCORE score;
    int n, id;
    char *p, c;
    while (1) {
        p = fgets(buf, BUF_SIZE, fin);
        if (!p) break;
        strncpy(score.title, buf, sizeof(score.title));
        score.title[sizeof(score.title)-1] = 0;

        p = fgets(buf, BUF_SIZE, fin);
        score.score_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        score.work_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &wtb = (BITMAP&)score.work_type;
        wtb.clear(WORK_TYPE_NWORDS);
        set_bits(buf, wtb, WORK_TYPE_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        score.period_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &ib = (BITMAP&)score.inst;
        ib.clear(INSTRUMENT_NWORDS);
        set_bits(buf, ib, INSTRUMENT_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        set_inst_combos(buf, score.inst_combos);

        p = fgets(buf, BUF_SIZE, fin);
        score.comp_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &nb = (BITMAP&)score.comp_nationality;
        nb.clear(NATIONALITY_NWORDS);
        set_bits(buf, nb, NATIONALITY_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        score.comp_sex = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        score.license_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        score.is_arrangement = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        score.publisher_id = atoi(buf);

        fwrite(&score, sizeof(score), 1, fout);
    }
    fclose(fout);
    fclose(fin);
}

void do_recs() {
    FILE* fin = fopen("data/digest_rec.txt", "r");
    FILE* fout = fopen("data/digest_rec.bin", "w");
    char buf[BUF_SIZE];
    REC rec;
    int n, id;
    char *p, c;
    while (1) {
        p = fgets(buf, BUF_SIZE, fin);
        if (!p) break;
        strncpy(rec.title, buf, sizeof(rec.title));
        rec.title[sizeof(rec.title)-1] = 0;

        p = fgets(buf, BUF_SIZE, fin);
        rec.rec_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        rec.work_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &wtb = (BITMAP&)rec.work_type;
        wtb.clear(WORK_TYPE_NWORDS);
        set_bits(buf, wtb, WORK_TYPE_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        rec.period_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &ib = (BITMAP&)rec.inst;
        ib.clear(INSTRUMENT_NWORDS);
        set_bits(buf, ib, INSTRUMENT_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        set_inst_combos(buf, rec.inst_combos);

        p = fgets(buf, BUF_SIZE, fin);
        rec.comp_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        BITMAP &nb = (BITMAP&)rec.comp_nationality;
        nb.clear(NATIONALITY_NWORDS);
        set_bits(buf, nb, NATIONALITY_MAX_ID);

        p = fgets(buf, BUF_SIZE, fin);
        rec.comp_sex = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        rec.license_id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        rec.is_arrangement = atoi(buf);

        fwrite(&rec, sizeof(rec), 1, fout);
    }
    fclose(fout);
    fclose(fin);
}

void do_inst_combos() {
    FILE* fin = fopen("data/digest_ic.txt", "r");
    FILE* fout = fopen("data/digest_ic.bin", "w");
    char buf[BUF_SIZE];
    INST_COMBO ic;
    vector<int> vec;
    char *p;
    while (1) {
        memset((void*)&ic, 0, sizeof(ic));
        p = fgets(buf, BUF_SIZE, fin);
        if (!p) break;
        ic.id = atoi(buf);

        p = fgets(buf, BUF_SIZE, fin);
        parse_ints(buf, vec);
        int i=0;
        for (int j: vec) {
            ic.inst_id[i++] = j;
        }

        p = fgets(buf, BUF_SIZE, fin);
        parse_ints(buf, vec);
        i = 0;
        for (int j: vec) {
            ic.count[i++] = j;
        }
        fwrite(&ic, sizeof(ic), 1, fout);
    }
    fclose(fout);
    fclose(fin);
}

int main(int, char**) {
    printf("doing works\n");
    do_works();
    printf("doing scores\n");
    do_scores();
    printf("doing recordings\n");
    do_recs();
    printf("doing inst combos\n");
    do_inst_combos();
}
