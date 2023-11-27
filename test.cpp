#include <cstdio>
#include <cstring>

int main(int, char**) {
    FILE* f = fopen("temp", "r");
    char buf[256];
    int x;
    char c;
#if 0
    fgets(buf, 256, f);
    sscanf(buf, "%d", &x);
#else
    //fscanf(f, "%d\n", &x);
    fscanf(f, "%d%c", &x, &c);
#endif
    printf("pos: %ld\n", ftell(f));
    printf("x: %d\n", x);
    while (1) {
        char* p = fgets(buf, 256, f);
        if (!p) break;
        printf("buf: '%s'\n", buf);
    }
#if 0

    const char* x = "foobar";
    int n=0;
    for (int i=0; i<200000; i++) {
        //if (strstr(x, "bar")) n++;
        n++;
    }
    printf("%d\n", n);
#endif
}
